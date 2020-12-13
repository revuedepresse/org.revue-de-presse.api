<?php

namespace App\PublishersList\Producer;

use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Contracts\Translation\TranslatorInterface;

use App\Twitter\Infrastructure\Amqp\Command\AggregateAwareCommand;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;


/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceTimelyStatusMessagesCommand extends AggregateAwareCommand implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    /**
     * @var \OldSound\RabbitMqBundle\RabbitMq\Producer
     */
    private $producer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    public $statusDirectory;

    public function configure()
    {
        $this->setName('weaving_the_web:amqp:produce:timely_statuses')
            ->setDescription('Produce an AMQP message to get a timely status')
        ->setAliases(array('wtw:amqp:prd:tst'));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|mixed|null
     * @throws SuspendedAccountException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        /** @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer */
        $this->producer = $this->getContainer()->get(
            'old_sound_rabbit_mq.weaving_the_web_amqp.producer.timely_status_producer'
        );

        $this->setUpDependencies();

        $this->producer->setContentType('application/json');

        $totalStatuses = $this->countStatuses();
        $itemsPerPage = 10000;

        $totalPages = ceil($totalStatuses / $itemsPerPage);
        $offset = 0;

        do {
            $this->publishTimelyStatusMessages($offset, $itemsPerPage);

            $offset++;
        } while ($offset < $totalPages);

        $this->output->writeln('All timely statuses messages have been produced successfully');
    }

    private function setUpDependencies()
    {
        $this->setUpLogger();
        $this->setupAggregateRepository();

        $this->translator = $this->getContainer()->get('translator');
    }

    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function countStatuses(): int
    {
        $query = <<<QUERY
            SELECT count(s.ust_id) total_statuses
            FROM weaving_aggregate a,
            weaving_status_aggregate sa,
            weaving_status s 
            WHERE sa.aggregate_id = a.id
            AND s.ust_id = sa.status_id
            AND ust_created_at > DATE_SUB(now(), INTERVAL 3 WEEK)
QUERY;
        $statement = $this->entityManager->getConnection()->executeQuery($query);
        $records = $statement->fetchAll();

        return (int) $records[0]['total_statuses'];
    }

    /**
     * @param $offset
     * @param $itemsPerPage
     * @throws \Doctrine\DBAL\DBALException
     */
    private function publishTimelyStatusMessages($offset, $itemsPerPage): void
    {
        $query = <<<QUERY
            SELECT 
            sa.status_id,
            a.id aggregate_id,
            a.name aggregate_name,
            s.ust_created_at publication_date_time,
            s.ust_full_name member_name,
            s.ust_api_document json
            FROM weaving_aggregate a,
            weaving_status_aggregate sa,
            weaving_status s 
            WHERE sa.aggregate_id = a.id
            AND s.ust_id = sa.status_id
            AND ust_created_at > DATE_SUB(now(), INTERVAL 3 WEEK )
            ORDER BY s.ust_created_at DESC
            LIMIT ?, ?
QUERY;

        $statement = $this->entityManager->getConnection()->executeQuery(
            $query,
            [
                $offset,
                $itemsPerPage,
            ],
            [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
            ]
        );
        $records = $statement->fetchAll();

        $records = array_map(
            function (array $record) {
                $document = json_decode($record['json'], true);
                $record['date_time'] = new \DateTime($document['created_at']);
                $record['created_at'] = $document['created_at'];

                return $record;
            },
            $records
        );

        usort($records, function ($leftRecord, $rightRecord) {
            if ($leftRecord['date_time'] === $rightRecord['date_time']) {
                return 0;
            }

            if ($leftRecord['date_time'] < $rightRecord['date_time']) {
                return 1;
            }

            return -1;
        });

        $records = array_map(
            function ($record) {
                $record['time_range'] = $this->mapDateToTimeRange($record['date_time']);

               return $record;
            },
            $records
        );

        $statusesByTimeRanges = [
            'statusesPublishedFromFiveMinutesAgoToNow' => array_filter(
                $records,
                function ($record) {
                    return $record['time_range'] === self::RANGE_SINCE_5_MIN_AGO;
                }
            ),
            'statusesPublishedFromTenMinutesAgoToFiveMinutesAgo' => array_filter(
                $records,
                function ($record) {
                    return $record['time_range'] === self::RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO;
                }
            ),
            'statusesPublishedFromThirtyMinutesAgoToTenMinutesAgo' => array_filter(
                $records,
                function ($record) {
                    return $record['time_range'] === self::RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO ;
                }
            ),
            'statusesPublishedFromADayAgoToThirtyMinutesAgo' => array_filter(
                $records,
                function ($record) {
                    return $record['time_range'] === self::RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO;
                }
            ),
            'statusesPublishedFromAWeekAgoToADayAgo' => array_filter(
                $records,
                function ($record) {
                    return $record['time_range'] === self::RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO;
                }
            ),
            'statusesPublishedOverAWeeksAgo' => array_filter(
                $records,
                function ($record) {
                    return $record['time_range'] === self::RANGE_OVER_1_WEEK_AGO;
                }
            ),
        ];

        array_walk(
            $statusesByTimeRanges,
            function ($records) {
                if (count($records) === 0) {
                    return;
                }

                $messageBody = [
                    'time_range' => $records[0]['time_range'],
                    'records' => array_map(
                        function ($record) {
                            return [
                                'status_id' => (int) trim($record['status_id']),
                                'aggregate_id' => $record['aggregate_id'],
                                'aggregate_name' => $record['aggregate_name'],
                                'publication_date_time' => $record['publication_date_time'],
                                'member_name' => $record['member_name'],
                                'created_at' => $record['created_at'],
                            ];
                        },
                        $records
                    )
                ];

                $this->producer->publish(serialize(json_encode($messageBody)));
            }
        );
    }
}

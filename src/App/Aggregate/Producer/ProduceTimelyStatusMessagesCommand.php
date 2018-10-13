<?php

namespace App\Aggregate\Producer;

use App\Operation\OperationClock;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Translation\TranslatorInterface;

use WeavingTheWeb\Bundle\AmqpBundle\Command\AggregateAwareCommand;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;


/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProduceTimelyStatusMessagesCommand extends AggregateAwareCommand
{
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
QUERY
;

        $statement = $this->entityManager->getConnection()->executeQuery($query);
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

        array_walk(
            $records,
            function ($record) {
                $messageBody = [
                    'status_id' => intval(trim($record['status_id'])),
                    'aggregate_id' => $record['aggregate_id'],
                    'aggregate_name' => $record['aggregate_name'],
                    'publication_date_time' => $record['publication_date_time'],
                    'member_name' => $record['member_name'],
                    'created_at' => $record['created_at'],
                ];
                $this->producer->publish(serialize(json_encode($messageBody)));
            }
        );
    }

    private function setUpDependencies()
    {
        $this->setUpLogger();
        $this->setupAggregateRepository();

        $this->translator = $this->getContainer()->get('translator');
    }
}

<?php

namespace App\Aggregate\Consumer;

use App\Accessor\StatusAccessor;
use App\Aggregate\AggregateAwareTrait;
use App\Aggregate\Entity\TimelyStatus;
use App\Aggregate\Repository\TimelyStatusRepository;
use App\Amqp\AmqpMessageAwareTrait;
use App\Console\CommandReturnCodeAwareInterface;
use App\Conversation\ConversationAwareTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use App\Api\Repository\AggregateRepository;

class TimelyStatusConsumer implements ConsumerInterface, CommandReturnCodeAwareInterface
{
    use AggregateAwareTrait;
    use ConversationAwareTrait;
    use AmqpMessageAwareTrait;

    const ERROR_CODE_USER_NOT_FOUND = 100;

    /**
     * @var EntityManager
     */
    public $entityManager;

    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @var StatusAccessor
     */
    public $statusAccessor;

    /**
     * @var TimelyStatusRepository
     */
    public $timelyStatusRepository;

    /**
     * @var \App\Member\Repository\MemberRepository
     */
    protected $userRepository;

    /**
     * @param EntityRepository $userRepository
     */
    public function setUserRepository(EntityRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param AmqpMessage $message
     * @return bool|mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function execute(AMQPMessage $message)
    {
        $timelyStatus = null;

        try {
            $options = $this->parseMessage($message);
            $this->logger->info(sprintf(
                'About to save timely statuses for time range #%d',
                $options['time_range']
            ));

            $totalProcessedEntities = 0;
            $itemsPerFlushingWindow = 1000;

            array_walk(
                $options['records'],
                function ($properties) use (&$totalProcessedEntities, $itemsPerFlushingWindow) {
                    $timelyStatus = $this->timelyStatusRepository->fromArray($properties);
                    $this->timelyStatusRepository->saveTimelyStatus($timelyStatus, $doNotFlush = true);
                    $totalProcessedEntities++;

                    if ($totalProcessedEntities % $itemsPerFlushingWindow === 0) {
                        $this->logger->info(
                            sprintf(
                                '%d timely statuses have been saved successfully',
                                $itemsPerFlushingWindow
                            )
                        );
                        $this->entityManager->flush();
                    }

                    return $timelyStatus;
                }
            );
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return self::RETURN_STATUS_FAILURE;
        }

        $this->entityManager->flush();

        $this->logger->info(
            sprintf(
                '%d timely statuses have been saved successfully',
                count($options['records'])
            )
        );

        return self::RETURN_STATUS_SUCCESS;
    }
}

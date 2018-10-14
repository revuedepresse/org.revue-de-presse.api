<?php

namespace App\Aggregate\Consumer;

use App\Accessor\StatusAccessor;
use App\Aggregate\AggregateAwareTrait;
use App\Aggregate\Entity\TimelyStatus;
use App\Aggregate\Repository\TimelyStatusRepository;
use App\Amqp\AmqpMessageAwareTrait;
use App\Conversation\ConversationAwareTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\Test\LoggerInterfaceTest;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;

class TimelyStatusConsumer implements ConsumerInterface
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
     * @var LoggerInterfaceTest
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
     * @var \WTW\UserBundle\Repository\UserRepository
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
            $timelyStatus = $this->timelyStatusRepository->fromArray($options);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        $this->timelyStatusRepository->saveTimelyStatus($timelyStatus);

        return $timelyStatus instanceof TimelyStatus;
    }
}

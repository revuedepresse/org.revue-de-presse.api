<?php

namespace App\Membership\Infrastructure\Consumer;

use App\Twitter\Infrastructure\Amqp\AmqpMessageAwareTrait;
use App\Membership\Infrastructure\Repository\NetworkRepository;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class NetworkConsumer implements ConsumerInterface
{
    use AmqpMessageAwareTrait;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var NetworkRepository
     */
    public $networkRepository;

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
        try {
            $members = $this->parseMessage($message);
            $this->networkRepository->saveNetwork($members);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        return true;
    }
}

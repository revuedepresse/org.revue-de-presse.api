<?php

namespace App\Console\EventSubscriber;

use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventsSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::ERROR => [
                ['logException', 0],
                ['notifyException', -10]
            ]
        ];
    }


    public function logException(ConsoleErrorEvent $event)
    {
        $exception = $event->getError();
        $this->logger->critical($exception->getMessage());
    }

    /**
     * @param ConsoleErrorEvent $event
     */
    public function notifyException(ConsoleErrorEvent $event)
    {
        $exception = $event->getError();

        if (
            $exception->getTrace()[0]['function'] === 'error_handler' &&
            $exception->getTrace()[0]['class'] === 'PhpAmqpLib\Wire\IO\StreamIO'
        ) {
            $event->setException(new \Exception('Could not connect to RabbitMQ server'));

            return;
        }

        if ($exception instanceof ConnectionException) {
            $event->setError(
                new \Exception(
                    'Could not connect to the database',
                    $exception->getCode()
                )
            );

            return;
        }
    }
}

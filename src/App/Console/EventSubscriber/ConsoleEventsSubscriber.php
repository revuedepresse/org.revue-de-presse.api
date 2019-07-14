<?php

namespace App\Console\EventSubscriber;

use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
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
            ConsoleEvents::EXCEPTION => [
                ['logException', 0],
                ['notifyException', -10]
            ]
        ];
    }


    public function logException(ConsoleExceptionEvent $event)
    {
        $exception = $event->getException();
        $this->logger->critical($exception->getMessage());
    }

    /**
     * @param ConsoleExceptionEvent $event
     */
    public function notifyException(ConsoleExceptionEvent $event)
    {
        $exception = $event->getException();

        if (
            $exception->getTrace()[0]['function'] === 'error_handler' &&
            $exception->getTrace()[0]['class'] === 'PhpAmqpLib\Wire\IO\StreamIO'
        ) {
            $event->setException(new \Exception('Could not connect to RabbitMQ server'));

            return;
        }

        if ($exception instanceof ConnectionException) {
            $event->setException(
                new \Exception(
                    'Could not connect to the database',
                    $exception->getCode()
                )
            );

            return;
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Console\EventSubscriber;

use Doctrine\DBAL\Exception\ConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventsSubscriber implements EventSubscriberInterface
{
    public LoggerInterface $logger;

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::ERROR => [
                ['logException', 0],
                ['notifyException', -10]
            ]
        ];
    }


    public function logException(ConsoleErrorEvent $event): void
    {
        $exception = $event->getError();
        $this->logger->critical($exception->getMessage());
    }

    public function notifyException(ConsoleErrorEvent $event): void
    {
        $exception = $event->getError();

        if ($exception instanceof ConnectionException) {
            $event->setError(
                new \Exception(
                    'Could not connect to the database',
                    $exception->getCode()
                )
            );
        }
    }
}

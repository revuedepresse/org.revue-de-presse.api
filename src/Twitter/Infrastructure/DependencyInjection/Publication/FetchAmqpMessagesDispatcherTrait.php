<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Infrastructure\Amqp\MessageBus\FetchAmqpMessagesDispatcherInterface;

trait FetchAmqpMessagesDispatcherTrait
{
    private FetchAmqpMessagesDispatcherInterface $fetchAmqpMessagesDispatcher;

    public function setFetchAmqpMessagesDispatcher(FetchAmqpMessagesDispatcherInterface $fetchPublicationsAmqpMessagesDispatcher): self
    {
        $this->fetchAmqpMessagesDispatcher = $fetchPublicationsAmqpMessagesDispatcher;

        return $this;
    }
}

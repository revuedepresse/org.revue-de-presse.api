<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Infrastructure\Amqp\MessageBus\FetchTweetsAmqpMessagesDispatcherInterface;

trait FetchTweetsAmqpMessagesDispatcherTrait
{
    private FetchTweetsAmqpMessagesDispatcherInterface $fetchTweetsAmqpMessagesDispatcher;

    public function setFetchTweetsAmqpMessagesDispatcher(FetchTweetsAmqpMessagesDispatcherInterface $fetchTweetsAmqpMessagesDispatcher): self
    {
        $this->fetchTweetsAmqpMessagesDispatcher = $fetchTweetsAmqpMessagesDispatcher;

        return $this;
    }
}

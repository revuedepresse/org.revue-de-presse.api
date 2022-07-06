<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Infrastructure\Amqp\MessageBus\FetchTweetsMessageDispatcherInterface;

trait FetchTweetsMessageDispatcherTrait
{
    private FetchTweetsMessageDispatcherInterface $fetchTweetsMessageDispatcher;

    public function setFetchTweetsMessageDispatcher(FetchTweetsMessageDispatcherInterface $fetchTweetsMessageDispatcher): self
    {
        $this->fetchTweetsMessageDispatcher = $fetchTweetsMessageDispatcher;

        return $this;
    }
}
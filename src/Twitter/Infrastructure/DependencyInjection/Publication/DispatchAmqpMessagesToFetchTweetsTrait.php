<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Infrastructure\Amqp\MessageBus\DispatchAmqpMessagesToFetchTweetsInterface;

trait DispatchAmqpMessagesToFetchTweetsTrait
{
    private DispatchAmqpMessagesToFetchTweetsInterface $DispatchAmqpMessagesToFetchTweets;

    public function setDispatchAmqpMessagesToFetchTweets(DispatchAmqpMessagesToFetchTweetsInterface $DispatchAmqpMessagesToFetchTweets): self
    {
        $this->DispatchAmqpMessagesToFetchTweets = $DispatchAmqpMessagesToFetchTweets;

        return $this;
    }
}
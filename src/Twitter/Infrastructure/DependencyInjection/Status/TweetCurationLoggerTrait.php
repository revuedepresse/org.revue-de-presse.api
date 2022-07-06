<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Status;

use App\Twitter\Infrastructure\Log\TweetCurationLoggerInterface;

trait TweetCurationLoggerTrait
{
    protected TweetCurationLoggerInterface $collectStatusLogger;

    public function setTweetCurationLogger(TweetCurationLoggerInterface $statusLogger): self
    {
        $this->collectStatusLogger = $statusLogger;

        return $this;
    }
}

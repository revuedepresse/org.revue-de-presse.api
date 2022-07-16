<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Status;

use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;

trait TweetRepositoryTrait
{
    private TweetRepositoryInterface $tweetRepository;

    public function setTweetRepository(TweetRepositoryInterface $tweetRepository): self
    {
        $this->tweetRepository = $tweetRepository;

        return $this;
    }
}

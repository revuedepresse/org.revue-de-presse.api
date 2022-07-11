<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Events;

use App\Twitter\Domain\Curation\Repository\TweetsBatchCollectedEventRepositoryInterface;

trait TweetBatchCollectedEventRepositoryTrait
{
    private TweetsBatchCollectedEventRepositoryInterface $tweetsBatchCollectedEventRepository;

    public function setTweetsBatchCollectedEventRepository(
        TweetsBatchCollectedEventRepositoryInterface $tweetsBatchCollectedEventRepository
    ): self {
        $this->tweetsBatchCollectedEventRepository = $tweetsBatchCollectedEventRepository;

        return $this;
    }
}

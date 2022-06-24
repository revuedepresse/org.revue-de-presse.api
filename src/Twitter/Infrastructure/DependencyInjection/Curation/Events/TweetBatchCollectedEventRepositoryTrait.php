<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Events;

use App\Twitter\Domain\Curation\Repository\TweetsBatchCollectedEventRepositoryInterface;

trait TweetBatchCollectedEventRepositoryTrait
{
    private TweetsBatchCollectedEventRepositoryInterface $publicationBatchCollectedEventRepository;

    public function setPublicationBatchCollectedEventRepository(
        TweetsBatchCollectedEventRepositoryInterface $publicationBatchCollectedEventRepository
    ): self {
        $this->tweetsBatchCollectedEventRepository = $publicationBatchCollectedEventRepository;

        return $this;
    }
}
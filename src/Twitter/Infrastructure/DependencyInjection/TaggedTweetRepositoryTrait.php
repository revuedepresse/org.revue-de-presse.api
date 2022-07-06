<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Domain\Publication\Repository\TaggedTweetRepositoryInterface;

trait TaggedTweetRepositoryTrait
{
    protected TaggedTweetRepositoryInterface $TaggedTweetRepository;

    public function setTaggedTweetRepository(TaggedTweetRepositoryInterface $TaggedTweetRepository): self
    {
        $this->TaggedTweetRepository = $TaggedTweetRepository;

        return $this;
    }
}
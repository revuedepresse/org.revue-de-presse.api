<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Domain\Publication\Repository\TaggedTweetRepositoryInterface;

trait TaggedTweetRepositoryTrait
{
    protected TaggedTweetRepositoryInterface $taggedTweetRepository;

    public function setTaggedTweetRepository(TaggedTweetRepositoryInterface $taggedTweetRepository): self
    {
        $this->taggedTweetRepository = $taggedTweetRepository;

        return $this;
    }
}

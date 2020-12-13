<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Domain\Publication\Repository\TaggedStatusRepositoryInterface;

trait TaggedStatusRepositoryTrait
{
    protected TaggedStatusRepositoryInterface $taggedStatusRepository;

    public function setTaggedStatusRepository(TaggedStatusRepositoryInterface $taggedStatusRepository): self
    {
        $this->taggedStatusRepository = $taggedStatusRepository;

        return $this;
    }
}
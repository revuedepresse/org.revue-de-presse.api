<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Domain\Repository\TaggedStatusRepositoryInterface;

trait TaggedStatusRepositoryTrait
{
    protected TaggedStatusRepositoryInterface $taggedStatusRepository;

    public function setTaggedStatusRepository(TaggedStatusRepositoryInterface $taggedStatusRepository): self
    {
        $this->taggedStatusRepository = $taggedStatusRepository;

        return $this;
    }
}
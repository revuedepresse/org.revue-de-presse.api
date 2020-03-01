<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Repository\Status\TaggedStatusRepositoryInterface;

trait TaggedStatusRepositoryTrait
{
    protected TaggedStatusRepositoryInterface $taggedStatusRepository;

    public function setTaggedStatusRepository(TaggedStatusRepositoryInterface $taggedStatusRepository): self
    {
        $this->taggedStatusRepository = $taggedStatusRepository;

        return $this;
    }
}
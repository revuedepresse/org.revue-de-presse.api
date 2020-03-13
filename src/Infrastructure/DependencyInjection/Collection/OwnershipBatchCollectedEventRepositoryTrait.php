<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Collection;

use App\Infrastructure\Collection\Repository\OwnershipBatchCollectedEventRepositoryInterface;
use App\Infrastructure\Collection\Repository\PublicationBatchCollectedEventRepositoryInterface;

trait OwnershipBatchCollectedEventRepositoryTrait
{
    private OwnershipBatchCollectedEventRepositoryInterface $ownershipBatchCollectedEventRepository;

    public function setOwnershipBatchCollectedEventRepository(
        OwnershipBatchCollectedEventRepositoryInterface $ownershipBatchCollectedEventRepository
    ): self {
        $this->ownershipBatchCollectedEventRepository = $ownershipBatchCollectedEventRepository;

        return $this;
    }
}
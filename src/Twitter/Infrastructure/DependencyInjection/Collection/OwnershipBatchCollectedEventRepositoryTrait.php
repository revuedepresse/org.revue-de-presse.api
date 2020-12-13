<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Collection;

use App\Twitter\Domain\Curation\Repository\OwnershipBatchCollectedEventRepositoryInterface;
use App\Twitter\Domain\Curation\Repository\PublicationBatchCollectedEventRepositoryInterface;

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
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Events;

use App\Twitter\Domain\Curation\Repository\ListsBatchCollectedEventRepositoryInterface;

trait ListBatchCollectedEventRepositoryTrait
{
    private ListsBatchCollectedEventRepositoryInterface $ownershipBatchCollectedEventRepository;

    public function setOwnershipBatchCollectedEventRepository(
        ListsBatchCollectedEventRepositoryInterface $ownershipBatchCollectedEventRepository
    ): self {
        $this->ownershipBatchCollectedEventRepository = $ownershipBatchCollectedEventRepository;

        return $this;
    }
}
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Events;

use App\Twitter\Domain\Curation\Repository\ListsBatchCollectedEventRepositoryInterface;

trait ListBatchCollectedEventRepositoryTrait
{
    private ListsBatchCollectedEventRepositoryInterface $listsBatchCollectedEventRepository;

    public function setListsBatchCollectedEventRepository(
        ListsBatchCollectedEventRepositoryInterface $listsBatchCollectedEventRepository
    ): self {
        $this->listsBatchCollectedEventRepository = $listsBatchCollectedEventRepository;

        return $this;
    }
}
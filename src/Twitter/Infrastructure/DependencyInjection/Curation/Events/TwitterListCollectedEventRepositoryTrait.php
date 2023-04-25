<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Events;

use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;

trait TwitterListCollectedEventRepositoryTrait
{
    private ListCollectedEventRepositoryInterface $publishersListCollectedEventRepository;

    public function setTwitterListCollectedEventRepository(
        ListCollectedEventRepositoryInterface $publishersListCollectedEventRepository
    ): self {
        $this->publishersListCollectedEventRepository = $publishersListCollectedEventRepository;

        return $this;
    }
}
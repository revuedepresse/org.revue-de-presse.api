<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Events;

use App\Twitter\Domain\Curation\Repository\ListCollectedEventRepositoryInterface;

trait PublishersListCollectedEventRepositoryTrait
{
    private ListCollectedEventRepositoryInterface $publishersListCollectedEventRepository;

    public function setPublishersListCollectedEventRepository(
        ListCollectedEventRepositoryInterface $publishersListCollectedEventRepository
    ): self {
        $this->publishersListCollectedEventRepository = $publishersListCollectedEventRepository;

        return $this;
    }
}
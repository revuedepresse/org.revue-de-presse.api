<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Collection;

use App\Twitter\Domain\Curation\Repository\PublishersListCollectedEventRepositoryInterface;

trait PublishersListCollectedEventRepositoryTrait
{
    private PublishersListCollectedEventRepositoryInterface $publishersListCollectedEventRepository;

    public function setPublishersListCollectedEventRepository(
        PublishersListCollectedEventRepositoryInterface $publishersListCollectedEventRepository
    ): self {
        $this->publishersListCollectedEventRepository = $publishersListCollectedEventRepository;

        return $this;
    }
}
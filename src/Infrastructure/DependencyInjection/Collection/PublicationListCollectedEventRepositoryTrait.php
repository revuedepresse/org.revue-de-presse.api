<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Collection;

use App\Infrastructure\Collection\Repository\PublicationListCollectedEventRepositoryInterface;

trait PublicationListCollectedEventRepositoryTrait
{
    private PublicationListCollectedEventRepositoryInterface $publicationListCollectedEventRepository;

    public function setPublicationListCollectedEventRepository(
        PublicationListCollectedEventRepositoryInterface $publicationListCollectedEventRepository
    ): self {
        $this->publicationListCollectedEventRepository = $publicationListCollectedEventRepository;

        return $this;
    }
}
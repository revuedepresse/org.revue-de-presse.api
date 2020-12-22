<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Collection;

use App\Twitter\Domain\Curation\Repository\PublicationBatchCollectedEventRepositoryInterface;

trait PublicationBatchCollectedEventRepositoryTrait
{
    private PublicationBatchCollectedEventRepositoryInterface $publicationBatchCollectedEventRepository;

    public function setPublicationBatchCollectedEventRepository(
        PublicationBatchCollectedEventRepositoryInterface $publicationBatchCollectedEventRepository
    ): self {
        $this->publicationBatchCollectedEventRepository = $publicationBatchCollectedEventRepository;

        return $this;
    }
}
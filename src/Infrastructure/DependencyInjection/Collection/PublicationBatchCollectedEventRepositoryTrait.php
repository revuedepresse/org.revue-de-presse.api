<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Collection;

use App\Domain\Curation\Repository\PublicationBatchCollectedEventRepositoryInterface;

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
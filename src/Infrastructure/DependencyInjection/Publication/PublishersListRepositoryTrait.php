<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Publication;

use App\Domain\PublishersList\Repository\PublishersListRepositoryInterface;

trait PublishersListRepositoryTrait
{
    private PublishersListRepositoryInterface $publishersListRepository;

    public function setPublishersListRepository(
        PublishersListRepositoryInterface $publishersListRepository
    ): self {
        $this->publishersListRepository = $publishersListRepository;

        return $this;
    }

}
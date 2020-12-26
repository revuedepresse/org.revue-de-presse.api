<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface;

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
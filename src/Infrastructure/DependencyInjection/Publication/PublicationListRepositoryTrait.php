<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Publication;

use App\Infrastructure\Repository\PublicationList\PublicationListRepositoryInterface;

trait PublicationListRepositoryTrait
{
    private PublicationListRepositoryInterface $publicationListRepository;

    public function setPublicationListRepository(PublicationListRepositoryInterface $publicationListRepository)
    {
        $this->publicationListRepository = $publicationListRepository;
    }

}
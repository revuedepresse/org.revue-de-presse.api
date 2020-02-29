<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Repository\PublicationRepositoryInterface;

trait PublicationRepositoryTrait
{
    protected PublicationRepositoryInterface $publicationRepository;

    public function setPublicationRepository(PublicationRepositoryInterface $publicationRepository)
    {
        $this->publicationRepository = $publicationRepository;
    }
}
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Domain\Publication\Repository\PublicationRepositoryInterface;

trait PublicationRepositoryTrait
{
    protected PublicationRepositoryInterface $publicationRepository;

    public function setPublicationRepository(PublicationRepositoryInterface $publicationRepository)
    {
        $this->publicationRepository = $publicationRepository;
    }
}
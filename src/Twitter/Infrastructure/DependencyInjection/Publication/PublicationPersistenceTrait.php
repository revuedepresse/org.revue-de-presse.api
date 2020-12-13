<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Infrastructure\Publication\Persistence\PublicationPersistenceInterface;

trait PublicationPersistenceTrait
{
    private PublicationPersistenceInterface $publicationPersistence;

    /**
     * @param PublicationPersistenceInterface $publicationPersistence
     */
    public function setPublicationPersistence(PublicationPersistenceInterface $publicationPersistence): void
    {
        $this->publicationPersistence = $publicationPersistence;
    }
}
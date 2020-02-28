<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Status\Persistence\PublicationPersistenceInterface;

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
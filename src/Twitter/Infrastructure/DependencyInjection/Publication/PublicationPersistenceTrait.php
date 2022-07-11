<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Domain\Persistence\TweetPersistenceLayerInterface;

trait PublicationPersistenceTrait
{
    private TweetPersistenceLayerInterface $publicationPersistence;

    /**
     * @param TweetPersistenceLayerInterface $publicationPersistence
     */
    public function setPublicationPersistence(TweetPersistenceLayerInterface $publicationPersistence): void
    {
        $this->publicationPersistence = $publicationPersistence;
    }
}

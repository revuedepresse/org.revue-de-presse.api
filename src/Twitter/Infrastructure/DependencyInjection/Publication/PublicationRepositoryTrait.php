<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Domain\Publication\Repository\TweetPublicationPersistenceLayerInterface;

trait PublicationRepositoryTrait
{
    protected TweetPublicationPersistenceLayerInterface $publicationRepository;

    public function setTweetPublicationPersistenceLayer(TweetPublicationPersistenceLayerInterface $publicationRepository)
    {
        $this->publicationRepository = $publicationRepository;
    }
}

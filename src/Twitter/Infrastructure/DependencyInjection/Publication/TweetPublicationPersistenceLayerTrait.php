<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Domain\Publication\Repository\TweetPublicationPersistenceLayerInterface;

trait TweetPublicationPersistenceLayerTrait
{
    protected TweetPublicationPersistenceLayerInterface $tweetPublicationPersistenceLayer;

    public function setTweetPublicationPersistenceLayer(TweetPublicationPersistenceLayerInterface $publicationRepository): self
    {
        $this->tweetPublicationPersistenceLayer = $publicationRepository;

        return $this;
    }
}

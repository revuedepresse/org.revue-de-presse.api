<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Persistence;

use App\Twitter\Domain\Persistence\TweetPersistenceLayerInterface;

trait TweetPersistenceLayerTrait
{
    private TweetPersistenceLayerInterface $tweetPersistenceLayer;

    public function setTweetPersistenceLayer(TweetPersistenceLayerInterface $tweetPersistenceLayer): void
    {
        $this->tweetPersistenceLayer = $tweetPersistenceLayer;
    }
}

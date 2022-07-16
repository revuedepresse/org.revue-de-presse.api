<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

use App\Twitter\Domain\Operation\Collection\CollectionInterface;

interface TweetPublicationPersistenceLayerInterface
{
    public function getLatestTweets(): CollectionInterface;

    public function persistTweetsCollection(CollectionInterface $collection): CollectionInterface;
}

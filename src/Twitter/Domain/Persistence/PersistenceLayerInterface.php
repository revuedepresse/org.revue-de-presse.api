<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Persistence;

use App\Search\Domain\Entity\SavedSearch;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;

interface PersistenceLayerInterface
{
    public function persistTweetsCollection(
        array $tweets,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): CollectionInterface;

    public function persistSearchBasedTweetsCollection(
        AccessToken $identifier,
        SavedSearch $savedSearch,
        array $rawTweets
    ): CollectionInterface;
}

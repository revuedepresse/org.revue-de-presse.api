<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Persistence;

use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;

interface PersistenceLayerInterface
{
    public function persistTweetsCollection(
        array $statuses,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): CollectionInterface;
}

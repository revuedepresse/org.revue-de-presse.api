<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Persistence;

use App\Twitter\Infrastructure\Api\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;

interface PublicationPersistenceInterface
{
    public function persistStatusPublications(
        array $statuses,
        AccessToken $identifier,
        PublishersList $aggregate = null
    ): CollectionInterface;
}
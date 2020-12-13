<?php
declare(strict_types=1);

namespace App\Infrastructure\Publication\Persistence;

use App\Infrastructure\Api\AccessToken\AccessToken;
use App\Infrastructure\Api\Entity\Aggregate;
use App\Infrastructure\Operation\Collection\CollectionInterface;

interface PublicationPersistenceInterface
{
    public function persistStatusPublications(
        array $statuses,
        AccessToken $identifier,
        Aggregate $aggregate = null
    ): CollectionInterface;
}
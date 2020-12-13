<?php
declare(strict_types=1);

namespace App\Infrastructure\Publication\Persistence;

use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;
use App\Infrastructure\Operation\Collection\CollectionInterface;

interface PublicationPersistenceInterface
{
    public function persistStatusPublications(
        array $statuses,
        AccessToken $identifier,
        Aggregate $aggregate = null
    ): CollectionInterface;
}
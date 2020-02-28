<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Persistence;

use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;
use App\Operation\Collection\CollectionInterface;

interface PublicationPersistenceInterface
{
    public function persistStatusPublications(
        array $statuses,
        AccessToken $identifier,
        Aggregate $aggregate = null
    ): CollectionInterface;
}
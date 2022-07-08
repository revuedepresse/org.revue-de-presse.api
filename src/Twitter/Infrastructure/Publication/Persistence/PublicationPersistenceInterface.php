<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Persistence;

use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Api\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;

interface PublicationPersistenceInterface
{
    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     */
    public function persistStatusPublications(
        array $statuses,
        AccessToken $identifier,
        PublishersListInterface $aggregate = null
    ): CollectionInterface;
}

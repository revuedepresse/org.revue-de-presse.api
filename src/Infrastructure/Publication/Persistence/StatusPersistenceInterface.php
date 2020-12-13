<?php
declare(strict_types=1);

namespace App\Infrastructure\Publication\Persistence;

use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;
use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Publication\StatusInterface;
use Doctrine\ORM\EntityManagerInterface;

interface StatusPersistenceInterface
{
    public function persistAllStatuses(
        array $statuses,
        AccessToken $accessToken,
        Aggregate $aggregate = null
    ): array;

    public function unarchiveStatus(
        StatusInterface $status,
        EntityManagerInterface $entityManager
    ): StatusInterface;

    public function savePublicationsForScreenName(
        array $statuses,
        string $screenName,
        CollectionStrategyInterface $collectionStrategy
    );
}
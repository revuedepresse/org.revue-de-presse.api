<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Persistence;

use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;
use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Status\StatusInterface;
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

    public function saveStatusForScreenName(
        array $statuses,
        string $screenName,
        CollectionStrategyInterface $collectionStrategy
    );
}
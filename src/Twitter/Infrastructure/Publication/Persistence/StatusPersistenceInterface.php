<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Persistence;

use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use Doctrine\ORM\EntityManagerInterface;

interface StatusPersistenceInterface
{
    public function persistAllStatuses(
        array $statuses,
        AccessToken $accessToken,
        PublishersListInterface $aggregate = null
    ): array;

    public function unarchiveStatus(
        StatusInterface $status,
        EntityManagerInterface $entityManager
    ): StatusInterface;
}

<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Persistence;

use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;

interface StatusPersistenceInterface
{
    public function persistAllStatuses(
        array $statuses,
        AccessToken $accessToken,
        Aggregate $aggregate = null
    ): array;
}
<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Persistor;

use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;

interface StatusPersistorInterface
{
    public function persistAllStatuses(
        array $statuses,
        AccessToken $accessToken,
        Aggregate $aggregate = null
    ): array;
}
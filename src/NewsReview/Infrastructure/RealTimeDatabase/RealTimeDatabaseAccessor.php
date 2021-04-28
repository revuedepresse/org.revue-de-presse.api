<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\RealTimeDatabase;

use DateTimeInterface;

interface RealTimeDatabaseAccessor
{
    public const DEFAULT_SNAPSHOT_ID = 1;

    public function getRealTimeDatabaseSnapshot(
        DateTimeInterface $date,
        bool $includeRetweets = false,
        int $snapshotId = self::DEFAULT_SNAPSHOT_ID
    ): array;
}
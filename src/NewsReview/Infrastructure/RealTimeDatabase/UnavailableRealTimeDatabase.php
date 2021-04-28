<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\RealTimeDatabase;

use DateTimeInterface;

class UnavailableRealTimeDatabase implements RealTimeDatabaseAccessor
{
    public static function build()
    {
        return new self();
    }

    public function getRealTimeDatabaseSnapshot(
        DateTimeInterface $date,
        bool $includeRetweets = false,
        int $snapshotId = self::DEFAULT_SNAPSHOT_ID
    ): array {
        return [];
    }
}
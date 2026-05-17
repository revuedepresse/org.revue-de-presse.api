<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Snapshot;

interface SnapshotReader
{
    /**
     * @return array<int|string, mixed>
     */
    public function read(string $date): array;
}

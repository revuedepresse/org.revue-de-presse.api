<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Snapshot\SnapshotReader;

final class InMemorySnapshotReader implements SnapshotReader
{
    public function __construct(private array $snapshotsByDate = [])
    {
    }

    public function read(string $date): array
    {
        return $this->snapshotsByDate[$date] ?? [];
    }
}

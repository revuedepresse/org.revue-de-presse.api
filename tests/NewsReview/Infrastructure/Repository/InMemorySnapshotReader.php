<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Snapshot\PublisherList;
use App\NewsReview\Domain\Snapshot\SnapshotReader;

final class InMemorySnapshotReader implements SnapshotReader
{
    /**
     * @param array<string, array<int|string, mixed>> $snapshotsByDate French media snapshots
     * @param array<string, array<string, array<int|string, mixed>>> $snapshotsByList other lists, keyed by list value then date
     */
    public function __construct(
        private array $snapshotsByDate = [],
        private array $snapshotsByList = [],
    ) {
    }

    public function read(string $date, PublisherList $list = PublisherList::FrenchMedia): array
    {
        if ($list === PublisherList::FrenchMedia) {
            return $this->snapshotsByDate[$date] ?? [];
        }

        return $this->snapshotsByList[$list->value][$date] ?? [];
    }
}

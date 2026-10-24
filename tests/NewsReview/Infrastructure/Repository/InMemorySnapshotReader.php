<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Snapshot\PublisherList;
use App\NewsReview\Domain\Snapshot\Ranking;
use App\NewsReview\Domain\Snapshot\SnapshotReader;

final class InMemorySnapshotReader implements SnapshotReader
{
    /**
     * @param array<string, array<int|string, mixed>> $snapshotsByDate French media first-sighting snapshots
     * @param array<string, array<string, array<int|string, mixed>>> $snapshotsByList other lists' first-sighting snapshots, keyed by list value then date
     * @param array<string, array<string, array<int|string, mixed>>> $endOfDaySnapshotsByList end-of-day snapshots, keyed by list value then date
     */
    public function __construct(
        private array $snapshotsByDate = [],
        private array $snapshotsByList = [],
        private array $endOfDaySnapshotsByList = [],
    ) {
    }

    public function read(
        string $date,
        PublisherList $list = PublisherList::FrenchMedia,
        Ranking $ranking = Ranking::FirstSighting,
    ): array {
        if ($ranking === Ranking::EndOfDay) {
            return $this->endOfDaySnapshotsByList[$list->value][$date] ?? [];
        }

        if ($list === PublisherList::FrenchMedia) {
            return $this->snapshotsByDate[$date] ?? [];
        }

        return $this->snapshotsByList[$list->value][$date] ?? [];
    }
}

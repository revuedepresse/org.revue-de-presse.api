<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain\Snapshot;

use App\NewsReview\Domain\Snapshot\Ranking;
use App\NewsReview\Domain\Snapshot\UnknownRanking;
use PHPUnit\Framework\TestCase;

final class RankingTest extends TestCase
{
    public function test_missing_value_falls_back_to_the_configured_default(): void
    {
        self::assertSame(Ranking::FirstSighting, Ranking::fromQuery(null, Ranking::FirstSighting));
        self::assertSame(Ranking::EndOfDay, Ranking::fromQuery('', Ranking::EndOfDay));
    }

    public function test_known_values(): void
    {
        self::assertSame(Ranking::FirstSighting, Ranking::fromQuery('first-sighting', Ranking::EndOfDay));
        self::assertSame(Ranking::EndOfDay, Ranking::fromQuery('end-of-day', Ranking::FirstSighting));
    }

    public function test_unknown_value_is_rejected(): void
    {
        $this->expectException(UnknownRanking::class);

        Ranking::fromQuery('../../etc', Ranking::FirstSighting);
    }

    public function test_only_end_of_day_lives_in_its_own_sub_directory(): void
    {
        self::assertNull(Ranking::FirstSighting->snapshotDirectory());
        self::assertSame('end-of-day', Ranking::EndOfDay->snapshotDirectory());
    }
}

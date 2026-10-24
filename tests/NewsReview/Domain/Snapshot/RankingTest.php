<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain\Snapshot;

use App\NewsReview\Domain\Snapshot\PublisherList;
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

    public function test_every_list_and_ranking_has_its_own_file_in_one_directory(): void
    {
        self::assertSame('2026-05-02.json', PublisherList::FrenchMedia->snapshotFileName('2026-05-02'));
        self::assertSame('2026-05-02.json', PublisherList::FrenchMedia->snapshotFileName('2026-05-02', Ranking::FirstSighting));
        self::assertSame('2026-05-02.fr.end-of-day.json', PublisherList::FrenchMedia->snapshotFileName('2026-05-02', Ranking::EndOfDay));
        self::assertSame('2026-05-02.coop-medias.first-sighting.json', PublisherList::CoopMedias->snapshotFileName('2026-05-02', Ranking::FirstSighting));
        self::assertSame('2026-05-02.coop-medias.end-of-day.json', PublisherList::CoopMedias->snapshotFileName('2026-05-02', Ranking::EndOfDay));
    }
}

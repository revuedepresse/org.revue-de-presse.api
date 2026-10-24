<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain\EndOfDay;

use App\NewsReview\Domain\EndOfDay\Candidate;
use App\NewsReview\Domain\EndOfDay\EndOfDayRebuilder;
use App\NewsReview\Domain\EndOfDay\ParisDay;
use PHPUnit\Framework\TestCase;

final class EndOfDayRebuilderTest extends TestCase
{
    public function test_ranks_accounts_on_their_best_post_at_each_cut_off(): void
    {
        $day = ParisDay::of('2026-10-23');
        [$at22, $atMidnight] = array_values($day->cutOffs());
        $before22 = $at22->modify('-1 hour');
        $between = $at22->modify('+1 hour');
        $after = $atMidnight->modify('+1 hour');

        $interactions = new FakeInteractions([
            // a.fr: early post overtaken after 22:00 by its other post
            'at://a/1' => [[$before22, $before22, $before22], [$before22]],
            'at://a/2' => [[$before22, $between, $between, $between], [$before22]],
            // b.fr: most reposts now, but mostly after midnight
            'at://b/1' => [[$before22, $after, $after, $after, $after], []],
            // c.fr: one repost before 22:00, ahead of b.fr on likes
            'at://c/1' => [[$before22], [$before22, $before22]],
        ]);

        $result = (new EndOfDayRebuilder($interactions))->rebuild($day, [
            new Candidate('at://a/1', 'a.fr', 11),
            new Candidate('at://a/2', 'a.fr', 12),
            new Candidate('at://b/1', 'b.fr', 21),
            new Candidate('at://c/1', 'c.fr', 31),
        ]);

        self::assertSame(
            [['a.fr', 'at://a/1', 3, 1], ['c.fr', 'at://c/1', 1, 2], ['b.fr', 'at://b/1', 1, 0]],
            array_map(static fn($e) => [$e->handle, $e->uri, $e->reposts, $e->likes], $result->topTen('22:00')),
        );
        self::assertSame(
            [['a.fr', 'at://a/2', 4, 1], ['c.fr', 'at://c/1', 1, 2], ['b.fr', 'at://b/1', 1, 0]],
            array_map(static fn($e) => [$e->handle, $e->uri, $e->reposts, $e->likes], $result->topTen('midnight')),
        );
    }

    public function test_stops_once_no_remaining_account_can_enter_the_top_ten(): void
    {
        $day = ParisDay::of('2026-10-23');
        $before = array_values($day->cutOffs())[0]->modify('-1 hour');
        $timeline = [];
        $candidates = [];
        // Eleven accounts with 20..10 reposts all before 22:00, then twenty with 1 repost.
        foreach (range(0, 10) as $i) {
            $timeline["at://big/$i"] = [array_fill(0, 20 - $i, $before), []];
            $candidates[] = new Candidate("at://big/$i", "big$i.fr", $i);
        }
        foreach (range(0, 19) as $i) {
            $timeline["at://small/$i"] = [[$before], []];
            $candidates[] = new Candidate("at://small/$i", "small$i.fr", 100 + $i);
        }
        $interactions = new FakeInteractions($timeline);

        $result = (new EndOfDayRebuilder($interactions))->rebuild($day, $candidates);

        self::assertCount(10, $result->topTen('midnight'));
        self::assertSame('big0.fr', $result->topTen('midnight')[0]->handle);
        self::assertSame([], array_filter($interactions->timelinesRead, static fn(string $uri): bool => str_starts_with($uri, 'at://small/')));
        self::assertLessThanOrEqual(11, count($interactions->timelinesRead));
    }

    public function test_reports_every_rebuilt_measurement_for_storage(): void
    {
        $day = ParisDay::of('2026-10-23');
        $before = array_values($day->cutOffs())[0]->modify('-1 hour');
        $interactions = new FakeInteractions(['at://a/1' => [[$before], [$before, $before]]]);

        $result = (new EndOfDayRebuilder($interactions))->rebuild($day, [new Candidate('at://a/1', 'a.fr', 7)]);

        self::assertSame(
            [['at://a/1', 7, '22:00', 1, 2], ['at://a/1', 7, 'midnight', 1, 2]],
            array_map(static fn($m) => [$m->candidate->uri, $m->candidate->statusId, $m->cutOff, $m->reposts, $m->likes], $result->measurements()),
        );
    }
}

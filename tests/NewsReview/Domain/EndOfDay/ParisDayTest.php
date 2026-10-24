<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain\EndOfDay;

use App\NewsReview\Domain\EndOfDay\ParisDay;
use PHPUnit\Framework\TestCase;

final class ParisDayTest extends TestCase
{
    private static function utc(\DateTimeImmutable $at): string
    {
        return $at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i');
    }

    public function test_a_summer_day_runs_from_22_00_to_22_00_utc(): void
    {
        $day = ParisDay::of('2026-10-23');

        self::assertSame('2026-10-22 22:00', self::utc($day->startsAt()));
        self::assertSame('2026-10-23 22:00', self::utc($day->endsAt()));
        self::assertSame(['22:00' => '2026-10-23 20:00', 'midnight' => '2026-10-23 22:00'], array_map(self::utc(...), $day->cutOffs()));
    }

    public function test_the_day_clocks_go_back_lasts_25_hours(): void
    {
        $day = ParisDay::of('2026-10-25');

        self::assertSame('2026-10-24 22:00', self::utc($day->startsAt()));
        self::assertSame(['22:00' => '2026-10-25 21:00', 'midnight' => '2026-10-25 23:00'], array_map(self::utc(...), $day->cutOffs()));
    }

    public function test_the_day_clocks_go_forward_lasts_23_hours(): void
    {
        $day = ParisDay::of('2026-03-29');

        self::assertSame('2026-03-28 23:00', self::utc($day->startsAt()));
        self::assertSame(['22:00' => '2026-03-29 20:00', 'midnight' => '2026-03-29 22:00'], array_map(self::utc(...), $day->cutOffs()));
    }

    public function test_rejects_anything_but_a_calendar_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ParisDay::of('2026-10-32');
    }
}

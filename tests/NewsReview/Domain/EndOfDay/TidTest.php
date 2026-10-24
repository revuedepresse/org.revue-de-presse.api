<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain\EndOfDay;

use App\NewsReview\Domain\EndOfDay\Tid;
use PHPUnit\Framework\TestCase;

final class TidTest extends TestCase
{
    public function test_a_record_key_tells_when_the_record_was_created(): void
    {
        self::assertSame('2026-10-24T11:56:49.545062+00:00', Tid::createdAt('3mymn7flova2x')?->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2026-10-24T11:34:55.431184+00:00', Tid::createdAt('3mymlyae4kk2i')?->format('Y-m-d\TH:i:s.uP'));
    }

    public function test_keys_that_are_not_timestamps_tell_nothing(): void
    {
        self::assertNull(Tid::createdAt('self'));
        self::assertNull(Tid::createdAt('3mymn7flova2X'));
        self::assertNull(Tid::createdAt('3mymn7flova2x0'));
    }
}

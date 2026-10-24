<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Console;

use App\NewsReview\Domain\EndOfDay\Interactions;
use App\NewsReview\Infrastructure\EndOfDay\BlueskyInteractions;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RebuildEndOfDayCommandWiringTest extends KernelTestCase
{
    public function test_the_console_offers_the_rebuild(): void
    {
        $application = new Application(self::bootKernel());

        self::assertTrue($application->has('highlights:rebuild-end-of-day'));
    }

    public function test_timelines_are_read_from_bluesky(): void
    {
        self::bootKernel();

        self::assertInstanceOf(BlueskyInteractions::class, self::getContainer()->get(Interactions::class));
    }
}

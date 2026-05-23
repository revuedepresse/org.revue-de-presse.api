<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Highlights;

use App\Newsletter\Infrastructure\Highlights\SnapshotHighlightsFetcher;
use App\NewsReview\Domain\Snapshot\SnapshotReader;
use PHPUnit\Framework\TestCase;

final class SnapshotHighlightsFetcherTest extends TestCase
{
    public function test_truncates_to_10_and_maps_fields(): void
    {
        $reader = new class implements SnapshotReader {
            public function read(string $date): array {
                $rows = [];
                for ($i = 1; $i <= 12; $i++) {
                    $rows[] = ['screen_name' => "pub{$i}", 'text' => "text {$i}", 'reposts' => $i, 'likes' => $i * 2, 'url' => "https://x/{$i}"];
                }
                return $rows;
            }
        };
        $fetcher = new SnapshotHighlightsFetcher($reader);
        $views = $fetcher->fetchTop10(new \DateTimeImmutable('2026-05-23'));
        self::assertCount(10, $views);
        self::assertSame('01', $views[0]->rank);
        self::assertSame('pub1', $views[0]->screen_name);
    }
}

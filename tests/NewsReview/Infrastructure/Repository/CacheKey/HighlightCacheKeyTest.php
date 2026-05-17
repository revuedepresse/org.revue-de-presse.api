<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository\CacheKey;

use App\NewsReview\Infrastructure\Repository\CacheKey\HighlightCacheKey;
use PHPUnit\Framework\TestCase;

class HighlightCacheKeyTest extends TestCase
{
    public function test_selected_aggregates_order_does_not_affect_key(): void
    {
        $params = [
            'startDate' => new \DateTime('2026-05-01T00:01:00'),
            'endDate'   => new \DateTime('2026-05-01T23:59:00'),
            'selectedAggregates' => ['b', 'a', 'c'],
        ];
        $params2 = $params;
        $params2['selectedAggregates'] = ['c', 'a', 'b'];

        self::assertSame(
            HighlightCacheKey::from($params),
            HighlightCacheKey::from($params2),
        );
    }

    public function test_distinct_param_combinations_produce_distinct_keys(): void
    {
        $base = [
            'startDate' => new \DateTime('2026-05-01'),
            'endDate'   => new \DateTime('2026-05-01'),
        ];
        $a = $base + ['distinctSources' => 1];
        $b = $base + ['distinctSources' => 0];

        self::assertNotSame(HighlightCacheKey::from($a), HighlightCacheKey::from($b));
    }

    public function test_truncates_dates_to_hour_precision(): void
    {
        $a = [
            'startDate' => new \DateTime('2026-05-01T10:00:00'),
            'endDate'   => new \DateTime('2026-05-01T10:00:00'),
        ];
        $b = [
            'startDate' => new \DateTime('2026-05-01T10:59:00'),
            'endDate'   => new \DateTime('2026-05-01T10:00:00'),
        ];

        self::assertSame(HighlightCacheKey::from($a), HighlightCacheKey::from($b));
    }
}

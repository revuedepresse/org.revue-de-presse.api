<?php
declare(strict_types=1);

namespace App\Tests\Trends\Infrastructure\Performance;

use App\Trends\Infrastructure\Performance\PerformanceMetrics;
use PHPUnit\Framework\TestCase;

/**
 * @group controller
 */
class PerformanceMetricsTest extends TestCase
{
    public function test_computes_basic_statistics_on_ten_samples(): void
    {
        $samples = [10.0, 20.0, 30.0, 40.0, 50.0, 60.0, 70.0, 80.0, 90.0, 100.0];
        $metrics = new PerformanceMetrics($samples, 2);

        self::assertSame(10, $metrics->count());
        self::assertSame(2, $metrics->errors());
        self::assertSame(10.0, $metrics->min());
        self::assertSame(100.0, $metrics->max());
        self::assertSame(55.0, $metrics->mean());
        // nearest-rank: ceil(0.50*10)-1 = 4 -> sorted[4] = 50.0
        self::assertSame(50.0, $metrics->p50());
        // ceil(0.95*10)-1 = 9 -> sorted[9] = 100.0
        self::assertSame(100.0, $metrics->p95());
        // ceil(0.99*10)-1 = 9 -> sorted[9] = 100.0
        self::assertSame(100.0, $metrics->p99());
    }

    public function test_handles_single_sample(): void
    {
        $metrics = new PerformanceMetrics([42.0]);

        self::assertSame(1, $metrics->count());
        self::assertSame(42.0, $metrics->min());
        self::assertSame(42.0, $metrics->max());
        self::assertSame(42.0, $metrics->p50());
        self::assertSame(42.0, $metrics->p99());
        self::assertSame(42.0, $metrics->mean());
    }

    public function test_unsorted_input_does_not_affect_results(): void
    {
        $metrics = new PerformanceMetrics([50.0, 10.0, 30.0, 20.0, 40.0]);

        self::assertSame(10.0, $metrics->min());
        self::assertSame(50.0, $metrics->max());
        self::assertSame(30.0, $metrics->mean());
        // ceil(0.5*5)-1 = 2 -> sorted[2] = 30.0
        self::assertSame(30.0, $metrics->p50());
    }

    public function test_throughput_uses_count_and_wall_clock(): void
    {
        $metrics = new PerformanceMetrics([1.0, 1.0, 1.0, 1.0]);

        self::assertSame(2.0, $metrics->throughput(2.0));
        self::assertSame(0.0, $metrics->throughput(0.0));
    }
}

<?php
declare(strict_types=1);

namespace App\Trends\Infrastructure\Performance;

use function array_sum;
use function ceil;
use function count;
use function sort;

final class PerformanceMetrics
{
    /** @var float[] */
    private readonly array $sortedSamples;

    /**
     * @param float[] $samplesMs Wall-clock latencies in milliseconds (>= 0).
     * @param int     $errors   Number of failed requests not represented in $samplesMs.
     */
    public function __construct(
        private readonly array $samplesMs,
        private readonly int $errors = 0,
    ) {
        $sorted = $samplesMs;
        sort($sorted);
        $this->sortedSamples = $sorted;
    }

    public function count(): int
    {
        return count($this->samplesMs);
    }

    public function errors(): int
    {
        return $this->errors;
    }

    public function min(): float
    {
        if (count($this->sortedSamples) === 0) {
            return 0.0;
        }

        return $this->sortedSamples[0];
    }

    public function max(): float
    {
        $n = count($this->sortedSamples);
        if ($n === 0) {
            return 0.0;
        }

        return $this->sortedSamples[$n - 1];
    }

    public function p50(): float
    {
        return $this->percentile(0.50);
    }

    public function p95(): float
    {
        return $this->percentile(0.95);
    }

    public function p99(): float
    {
        return $this->percentile(0.99);
    }

    public function mean(): float
    {
        $n = count($this->samplesMs);
        if ($n === 0) {
            return 0.0;
        }

        return array_sum($this->samplesMs) / $n;
    }

    public function throughput(float $wallClockSeconds): float
    {
        if ($wallClockSeconds <= 0.0) {
            return 0.0;
        }

        return $this->count() / $wallClockSeconds;
    }

    private function percentile(float $rank): float
    {
        $n = count($this->sortedSamples);
        if ($n === 0) {
            return 0.0;
        }

        $index = (int) ceil($rank * $n) - 1;
        if ($index < 0) {
            $index = 0;
        }
        if ($index >= $n) {
            $index = $n - 1;
        }

        return $this->sortedSamples[$index];
    }
}

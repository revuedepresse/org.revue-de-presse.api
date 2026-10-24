<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

final class Rebuild
{
    /**
     * @param array<string, list<RankedPost>> $topTens by cut-off
     * @param list<RebuiltMeasurement> $measurements
     */
    public function __construct(
        public readonly ParisDay $day,
        private readonly array $topTens,
        private readonly array $measurements,
    ) {
    }

    /** @return list<RankedPost> */
    public function topTen(string $cutOff): array
    {
        return $this->topTens[$cutOff] ?? [];
    }

    /** @return list<RebuiltMeasurement> */
    public function measurements(): array
    {
        return $this->measurements;
    }
}

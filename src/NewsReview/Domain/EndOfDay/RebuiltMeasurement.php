<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

/** A post's counts as they stood at a cut-off, rebuilt from its timeline. */
final class RebuiltMeasurement
{
    public function __construct(
        public readonly Candidate $candidate,
        // '22:00' or 'midnight'
        public readonly string $cutOff,
        public readonly \DateTimeImmutable $at,
        public readonly int $reposts,
        public readonly int $likes,
    ) {
    }
}

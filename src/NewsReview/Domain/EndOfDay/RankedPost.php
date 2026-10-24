<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

/** An account's best post at a cut-off, as it enters the top 10. */
final class RankedPost
{
    public function __construct(
        public readonly string $handle,
        public readonly string $uri,
        public readonly int $reposts,
        public readonly int $likes,
    ) {
    }
}

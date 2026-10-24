<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

/** A post published during the day by a member of the list. */
final class Candidate
{
    public function __construct(
        public readonly string $uri,
        public readonly string $handle,
        // The status its first fetched measurement belongs to.
        public readonly ?int $statusId,
    ) {
    }
}

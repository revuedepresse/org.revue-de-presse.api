<?php
declare(strict_types=1);

namespace App\Summary\Domain;

/**
 * One day's pre-computed thematic synthesis of the top-10 publications.
 * Stored on disk as markdown and read back at request time by the day-page.
 */
final readonly class DailySummary
{
    public function __construct(
        public string $date,      // YYYY-MM-DD
        public string $markdown,  // body, no front-matter
    ) {
    }
}

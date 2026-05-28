<?php
declare(strict_types=1);

namespace App\Summary\Domain;

/**
 * Persistence port for daily summaries. The generator writes via this port;
 * the read-side (API resource) reads. The filesystem adapter stores one
 * markdown file per date next to the existing snapshot JSON.
 */
interface DailySummaryRepository
{
    public function read(string $date): ?DailySummary;

    public function save(DailySummary $summary): void;

    public function exists(string $date): bool;
}

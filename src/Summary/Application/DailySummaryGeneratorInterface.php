<?php
declare(strict_types=1);

namespace App\Summary\Application;

use App\Summary\Domain\DailySummary;

/**
 * Generation seam between the Command (Symfony layer) and the concrete
 * Generator (Application layer). Exists primarily so tests can stub
 * generate() without instantiating ChatStreamer / SnapshotReader chains.
 */
interface DailySummaryGeneratorInterface
{
    /**
     * @return DailySummary|null null when the day's snapshot is missing or empty
     */
    public function generate(string $date): ?DailySummary;
}

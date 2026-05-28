<?php
declare(strict_types=1);

namespace App\Summary\Infrastructure\Repository;

use App\Summary\Domain\DailySummary;
use App\Summary\Domain\DailySummaryRepository;

/**
 * Stores one markdown file per date at
 * {projectDir}/src/Bluesky/Resources/{date}-summary.md, alongside the
 * existing top-10 snapshot JSON at {date}.json.
 *
 * Save is atomic (write to .tmp + rename) so a half-written file never
 * leaks into read traffic.
 */
final class FilesystemDailySummaryRepository implements DailySummaryRepository
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function read(string $date): ?DailySummary
    {
        $path = $this->path($date);
        if (!is_file($path)) {
            return null;
        }
        $body = file_get_contents($path);
        if ($body === false) {
            return null;
        }

        return new DailySummary(
            date: $date,
            markdown: $body,
            // publicationCount isn't persisted in the markdown body; we don't
            // need it on read (the day-page already shows the count from the
            // existing top-10 view). Defaults to 0 on read; -1 would be more
            // honest but breaks the int contract.
            publicationCount: 0,
        );
    }

    public function save(DailySummary $summary): void
    {
        $path = $this->path($summary->date);
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Failed to create directory: {$dir}");
        }

        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, $summary->markdown) === false) {
            throw new \RuntimeException("Failed to write summary tmp file: {$tmp}");
        }
        if (!rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException("Failed to atomic-rename summary into place: {$path}");
        }
    }

    public function exists(string $date): bool
    {
        return is_file($this->path($date));
    }

    private function path(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new \InvalidArgumentException("Invalid date: {$date} (expected YYYY-MM-DD)");
        }

        return $this->projectDir . '/src/Bluesky/Resources/' . $date . '-summary.md';
    }
}

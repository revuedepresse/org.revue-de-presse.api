<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Snapshot\SnapshotReader;
use Psr\Log\LoggerInterface;

final class FilesystemSnapshotReader implements SnapshotReader
{
    public function __construct(
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function read(string $date): array
    {
        $path = $this->projectDir . '/src/Bluesky/Resources/' . $date . '.json';
        if (!file_exists($path)) {
            $this->logger->info('snapshot missing', ['date' => $date]);

            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}

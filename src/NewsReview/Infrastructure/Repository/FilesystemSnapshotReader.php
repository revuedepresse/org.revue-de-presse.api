<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Snapshot\PublisherList;
use App\NewsReview\Domain\Snapshot\Ranking;
use App\NewsReview\Domain\Snapshot\SnapshotReader;
use Psr\Log\LoggerInterface;

final class FilesystemSnapshotReader implements SnapshotReader
{
    public function __construct(
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function read(
        string $date,
        PublisherList $list = PublisherList::FrenchMedia,
        Ranking $ranking = Ranking::FirstSighting,
    ): array {
        $path = $this->projectDir . '/src/Bluesky/Resources/' . $list->snapshotFileName($date, $ranking);
        if (!file_exists($path)) {
            $this->logger->info('snapshot missing', ['date' => $date, 'list' => $list->value, 'ranking' => $ranking->value]);

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

<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\RealTimeDatabase\Firebase;

use App\NewsReview\Infrastructure\RealTimeDatabase\Firebase\Exception\UnvailableServiceAccountConfigurationFileException;
use App\NewsReview\Infrastructure\RealTimeDatabase\RealTimeDatabaseAccessor;
use App\NewsReview\Infrastructure\RealTimeDatabase\UnavailableRealTimeDatabase;
use DateTimeInterface;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Snapshot;
use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\Factory;
use Psr\Log\LoggerInterface;

class FirebaseAccessor implements RealTimeDatabaseAccessor
{
    private Database $database;

    private LoggerInterface $logger;

    public function __construct(
        ?Database $database,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->logger = $logger;
    }

    public static function getDatabaseAccessor(
        $serviceAccountConfig,
        $databaseUri,
        LoggerInterface $logger
    ): RealTimeDatabaseAccessor {
        try {
            $database = (new Factory)
                ->withServiceAccount($serviceAccountConfig)
                ->withDatabaseUri($databaseUri)
                ->createDatabase();

            return new self($database, $logger);
        } catch (InvalidArgumentException $e) {
            UnvailableServiceAccountConfigurationFileException::guardAgainstMissingServiceAccountConfigurationFile(
                $serviceAccountConfig,
                $logger
            );

            return UnavailableRealTimeDatabase::build();
        }
    }

    private function getFirebaseDatabaseSnapshot(
        DateTimeInterface $date,
        int $aggregateId = self::DEFAULT_SNAPSHOT_ID,
        bool $includeRetweets = false
    ): Snapshot {
        $path = '/'.implode(
                '/',
                [
                    'highlights',
                    $aggregateId,
                    $date->format('Y-m-d'),
                    $includeRetweets ? 'retweet' : 'status'
                ]
            );
        $this->logger->info(sprintf('About to access Firebase Path: "%s"', $path));
        $reference = $this->database->getReference($path);

        return $reference
            ->orderByChild('totalRetweets')
            ->getSnapshot();
    }

    public function getRealTimeDatabaseSnapshot(
        DateTimeInterface $date,
        bool $includeRetweets = false,
        int $snapshotId = self::DEFAULT_SNAPSHOT_ID
    ): array {
        $snapshot = $this->getFirebaseDatabaseSnapshot(
            $date,
            $snapshotId,
            $includeRetweets
        );

        $snapshotColumn = $snapshot->getValue();
        if ($snapshotColumn === null) {
            $snapshotColumn = [];
        }

        return $snapshotColumn;
    }
}
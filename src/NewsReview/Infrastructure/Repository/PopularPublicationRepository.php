<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Repository\PopularPublicationRepositoryInterface;
use App\NewsReview\Domain\Repository\SearchParamsInterface;
use App\Twitter\Infrastructure\Publication\Repository\HighlightRepository;
use DateTimeInterface;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Snapshot;
use Kreait\Firebase\Factory;
use Psr\Log\LoggerInterface;

class PopularPublicationRepository implements PopularPublicationRepositoryInterface
{
    private string $serviceAccountConfig;
    private string $databaseUri;

    private LoggerInterface $logger;

    private HighlightRepository $highlightRepository;

    public function __construct(
        string $serviceAccountConfig,
        string $databaseUri,
        HighlightRepository $highlightRepository,
        LoggerInterface $logger
    )
    {
        $this->serviceAccountConfig = $serviceAccountConfig;
        $this->databaseUri = $databaseUri;
        $this->logger = $logger;
        $this->highlightRepository = $highlightRepository;
    }

    private function getFirebaseDatabase(): Database
    {
        return (new Factory)
            ->withServiceAccount($this->serviceAccountConfig)
            ->withDatabaseUri($this->databaseUri)
            ->createDatabase();
    }

    private function getFirebaseDatabaseSnapshot(
        DateTimeInterface $date,
        bool $includeRetweets = false
    ): Snapshot {
        $database = $this->getFirebaseDatabase();

        $aggregateId = 1;
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
        $reference = $database->getReference($path);

        return $reference
            ->orderByChild('totalRetweets')
            ->getSnapshot();
    }

    public function findBy(SearchParamsInterface $searchParams): array {
        $snapshot = $this->getFirebaseDatabaseSnapshot(
            $searchParams->getParams()['startDate'],
            $searchParams->getParams()['includeRetweets']
         );

        $highlights = array_reverse($snapshot->getValue());
        $highlights = array_map(function (array $highlight) {
            return [
                'original_document' => $highlight['json'],
                'id' => $highlight['id'],
                'publicationDateTime' => $highlight['publishedAt'],
                'screen_name' => $highlight['username'],
                'last_update' => $highlight['checkedAt'],
                'total_retweets' => $highlight['totalRetweets'],
                'total_favorites' => $highlight['totalFavorites'],
            ];
        }, $highlights);

        $statuses = $this->highlightRepository->mapStatuses($searchParams, $highlights);

        return [
            'aggregates' => [],
            'statuses' => $statuses,
        ];
    }
}
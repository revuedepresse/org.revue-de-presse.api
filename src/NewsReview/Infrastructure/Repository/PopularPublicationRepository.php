<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Repository\PopularPublicationRepositoryInterface;
use App\NewsReview\Domain\Repository\SearchParamsInterface;
use App\NewsReview\Infrastructure\RealTimeDatabase\Firebase\FirebaseAccessor;
use App\Twitter\Infrastructure\Publication\Repository\HighlightRepository;
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

    public function findBy(SearchParamsInterface $searchParams): array {

        $databaseAccessor = FirebaseAccessor::getDatabaseAccessor(
            $this->serviceAccountConfig,
            $this->databaseUri,
            $this->logger
        );

        $snapshotColumn = $databaseAccessor->getRealTimeDatabaseSnapshot(
            $searchParams->getParams()['startDate'],
            $searchParams->getParams()['includeRetweets']
        );

        $highlights = array_reverse($snapshotColumn);
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
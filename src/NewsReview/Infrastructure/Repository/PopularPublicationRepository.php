<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Repository\PopularPublicationRepositoryInterface;
use App\NewsReview\Domain\Repository\PublishersListRouteRepositoryInterface;
use App\NewsReview\Domain\Repository\SearchParamsInterface;
use App\NewsReview\Domain\Exception\UnknownPublishersListException;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface;
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

    private PublishersListRepositoryInterface $publishersListRepository;

    private PublishersListRouteRepositoryInterface $publishersListRouteRepository;

    private string $defaultPublishersList;

    public function __construct(
        string $serviceAccountConfig,
        string $databaseUri,
        string $defaultPublishersList,
        HighlightRepository $highlightRepository,
        PublishersListRepositoryInterface $publishersListRepository,
        PublishersListRouteRepositoryInterface $publishersListRouteRepository,
        LoggerInterface $logger
    )
    {
        $this->serviceAccountConfig = $serviceAccountConfig;
        $this->databaseUri = $databaseUri;
        $this->defaultPublishersList = $defaultPublishersList;

        $this->highlightRepository = $highlightRepository;
        $this->publishersListRepository = $publishersListRepository;
        $this->publishersListRouteRepository = $publishersListRouteRepository;
        $this->logger = $logger;
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

        $publishersList = $this->publishersListRepository->findOneBy(['name' => $this->defaultPublishersList]);

        if (!($publishersList instanceof PublishersListInterface)) {
            UnknownPublishersListException::throws();
        }

        $path = '/'.implode(
            '/',
            [
                'highlights',
                $publishersList->publicId(),
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
        try {
            $snapshot = $this->getFirebaseDatabaseSnapshot(
                $searchParams->getParams()['startDate'],
                $searchParams->getParams()['includeRetweets']
             );
        } catch (UnknownPublishersListException $exception) {
            return [
                'links' => [],
                'statuses' => [],
            ];
        }

        $col = $snapshot->getValue();
        if ($col === null) {
            $col = [];
        }

        $highlights = array_reverse($col);
        $highlights = array_map(static function (array $highlight) {
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

        $publicationsListsRoutes = $this->publishersListRouteRepository->allPublishersRoutes();

        return [
            'links' => $publicationsListsRoutes->toArray(),
            'statuses' => $statuses,
        ];
    }

    public function getFallbackPublishersListFingerprint(): string
    {
        return sha1($this->defaultPublishersList);
    }
}
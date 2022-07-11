<?php
declare (strict_types=1);

namespace App\Trends\Infrastructure\Repository;

use App\Ownership\Domain\Exception\UnknownListException;
use App\Trends\Domain\Repository\PopularPublicationRepositoryInterface;
use App\Trends\Domain\Repository\SearchParamsInterface;
use App\Ownership\Domain\Repository\MembersListRepositoryInterface;
use App\Twitter\Domain\Publication\MembersListInterface;
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

    private MembersListRepositoryInterface $listRepository;

    private string $defaultPublishersList;

    public function __construct(
        string $serviceAccountConfig,
        string $databaseUri,
        string $defaultPublishersList,
        HighlightRepository $highlightRepository,
        MembersListRepositoryInterface $publishersListRepository,
        LoggerInterface $logger
    )
    {
        $this->serviceAccountConfig = $serviceAccountConfig;
        $this->databaseUri = $databaseUri;
        $this->defaultPublishersList = $defaultPublishersList;

        $this->highlightRepository = $highlightRepository;
        $this->listRepository = $publishersListRepository;
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

        $publishersList = $this->listRepository->findOneBy(['name' => $this->defaultPublishersList]);

        if (!($publishersList instanceof MembersListInterface)) {
            UnknownListException::throws();
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
        } catch (UnknownListException) {
            return [
                'aggregates' => [],
                'statuses' => [],
            ];
        }

        $col = $snapshot->getValue();
        if ($col === null) {
            $col = [];
        }

        $highlights = array_reverse($col);
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

<?php
declare (strict_types=1);

namespace App\Trends\Infrastructure\Repository;

use App\Ownership\Domain\Exception\UnknownListException;
use App\Ownership\Domain\Repository\MembersListRepositoryInterface;
use App\Trends\Domain\Repository\PopularPublicationRepositoryInterface;
use App\Trends\Domain\Repository\SearchParamsInterface;
use App\Ownership\Domain\Entity\MembersListInterface;
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

    /**
     * @throws \App\Conversation\Exception\InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     */
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

        $highlights = $snapshot->getValue();
        if ($highlights === null) {
            $highlights = [];
        }

        $tweets = $this->highlightRepository->mapStatuses($searchParams, array_reverse($highlights));

        return [
            'aggregates' => [],
            'statuses' => $tweets,
        ];
    }
}

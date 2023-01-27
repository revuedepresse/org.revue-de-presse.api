<?php
declare (strict_types=1);

namespace App\QualityAssurance\Infrastructure\Repository;

use App\Ownership\Domain\Exception\UnknownListException;
use App\Ownership\Domain\Repository\MembersListRepositoryInterface;
use App\Ownership\Domain\Entity\MembersListInterface;
use App\QualityAssurance\Domain\Repository\TrendsRepositoryInterface;
use App\Twitter\Infrastructure\Publication\Repository\HighlightRepository;
use DateTimeInterface;
use Kreait\Firebase\Database;
use Kreait\Firebase\Database\Snapshot;
use Kreait\Firebase\Factory;
use Psr\Log\LoggerInterface;

class TrendsRepository implements TrendsRepositoryInterface
{
    private string $serviceAccountConfig;
    private string $databaseUri;

    private LoggerInterface $logger;

    private MembersListRepositoryInterface $listRepository;

    private string $defaultPublishersList;

    public function __construct(
        string $serviceAccountConfig,
        string $databaseUri,
        string $defaultPublishersList,
        MembersListRepositoryInterface $publishersListRepository,
        LoggerInterface $logger
    )
    {
        $this->serviceAccountConfig = $serviceAccountConfig;
        $this->databaseUri = $databaseUri;
        $this->defaultPublishersList = $defaultPublishersList;
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
        string $tweetId,
        DateTimeInterface $date,
        bool $includeRetweets = true
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
                $includeRetweets ? 'retweet' : 'status',
                $tweetId,
                'json'
            ]
        );
        $this->logger->info(sprintf('About to access Firebase Path: "%s"', $path));
        $reference = $database->getReference($path);

        return $reference
            ->getSnapshot();
    }

    public function updateTweetDocument(
        string $tweetId,
        \DateTimeInterface $date,
        string $document
    ) {
        try {
            $snapshot = $this->getFirebaseDatabaseSnapshot(
                $tweetId,
                $date
             );
        } catch (UnknownListException) {
            return [
                'aggregates' => [],
                'statuses' => [],
            ];
        }

        try {
            $snapshot->getReference()->set($document);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw $e;
        }

        return $snapshot->getValue();
    }
}

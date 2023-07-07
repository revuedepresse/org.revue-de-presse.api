<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Search\Domain\Entity\SavedSearch;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\PersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\TweetPersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\TweetPublicationPersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetCurationLoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\TaggedTweetRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Infrastructure\Http\Exception\InsertDuplicatesException;
use App\Twitter\Infrastructure\Http\Normalizer\Normalizer;
use App\Twitter\Infrastructure\Http\Repository\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Publication\Mapping\MappingAwareInterface;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function count;
use const JSON_THROW_ON_ERROR;

class ArchivedTweetRepository extends ResourceRepository implements
    ExtremumAwareInterface,
    TweetRepositoryInterface
{
    use MemberRepositoryTrait;
    use PersistenceLayerTrait;
    use TweetPublicationPersistenceLayerTrait;
    use TweetCurationLoggerTrait;
    use TweetPersistenceLayerTrait;
    use TaggedTweetRepositoryTrait;
    use TimelyStatusRepositoryTrait;

    public ManagerRegistry $registry;

    public LoggerInterface $appLogger;

    public Connection $connection;

    public bool $shouldExtractProperties;

    /**
     * @throws \App\Twitter\Infrastructure\Http\Repository\Exception\TweetNotFoundException
     */
    public function byId(int $tweetId): TweetInterface
    {
        try {
            $tweet = $this->findOneBy(['statusId' => $tweetId]);

            if (!($tweet instanceof TweetInterface)) {
                throw new TweetNotFoundException(
                    sprintf(
                        'Tweet having id %s cannot be found in database.',
                        $tweetId
                    )
                );
            }

            return $tweet;
        } catch (\Exception $e) {
            throw new TweetNotFoundException($e->getMessage());
        }
    }

    public function extractTweetReach(TweetInterface $tweet): array
    {
        return $this->collectStatusLogger->extractTweetReach($tweet);
    }

    public function countCollectedStatuses(
        string $screenName,
               $extremumId,
        string $findingDirection = ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER
    ): ?int {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.hash) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        if ($findingDirection === ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER &&
            $extremumId < PHP_INT_MAX) {
            $queryBuilder->andWhere('s.statusId <= :maxId');
            $queryBuilder->setParameter('maxId', $extremumId);
        }

        if ($findingDirection === ExtremumAwareInterface::FINDING_IN_DESCENDING_ORDER) {
            $queryBuilder->andWhere('s.statusId >= :sinceId');
            $queryBuilder->setParameter('sinceId', $extremumId);
        }

        try {
            $singleResult = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            $singleResult = ['count_' => null];
        }

        return $singleResult['count_'];
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function howManyTweetsHaveBeenCollectedForMemberHavingUserName(string $screenName): int
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();

        return (int) $totalStatuses;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findLocalMaximum(
        string  $memberUsername,
        ?string $before = null
    ): array {
        $direction = self::FINDING_IN_ASCENDING_ORDER;
        if ($before === null) {
            $direction = self::FINDING_IN_DESCENDING_ORDER;
        }

        return $this->findNextExtremum($memberUsername, $direction, $before);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findNextExtremum(
        string  $memberUsername,
        string  $direction = self::FINDING_IN_ASCENDING_ORDER,
        ?string $before = null
    ): array {
        $member = $this->memberRepository->findOneBy([
            'twitter_username' => $memberUsername
        ]);
        if ($member instanceof MemberInterface) {
            if ($direction === self::FINDING_IN_DESCENDING_ORDER &&
                $member->maxTweetId() > 0) {
                return [
                    self::EXTREMUM_STATUS_ID => $member->maxTweetId(),
                    self::EXTREMUM_FROM_MEMBER => true
                ];
            }

            if ($direction === self::FINDING_IN_ASCENDING_ORDER &&
                $member->minTweetId() > 0) {
                return [
                    self::EXTREMUM_STATUS_ID => $member->minTweetId(),
                    self::EXTREMUM_FROM_MEMBER => true
                ];
            }
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
            ->andWhere('s.screenName = :screenName')
            ->andWhere('s.apiDocument is not null')
            ->orderBy('CAST(s.statusId AS bigint)', $direction)
            ->setMaxResults(1);

        $queryBuilder->setParameter('screenName', $memberUsername);

        if ($before) {
            $queryBuilder->andWhere('DATE(s.createdAt) <= :date');
            $queryBuilder->setParameter(
                'date',
                (new DateTime($before, new \DateTimeZone('UTC')))
                    ->format('Y-m-d')
            );
        }

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            $this->appLogger->info($exception->getMessage());

            if ($direction === self::FINDING_IN_ASCENDING_ORDER) {
                return [self::EXTREMUM_STATUS_ID => PHP_INT_MAX];
            }

            return [self::EXTREMUM_STATUS_ID => PHP_INT_MIN];
        }
    }

    /**
     * @param string $id
     *
     * @return array|TweetInterface|TaggedStatus
     * @throws Exception
     */
    public function findStatusIdentifiedBy(string $id)
    {
        $status = $this->findOneBy(['statusId' => $id]);
        if (!($status instanceof TweetInterface)) {
            return [];
        }

        if (!$this->shouldExtractProperties) {
            return $status;
        }

        $statusDocument = json_decode(
            $status->getApiDocument(),
            false,
            512,
            JSON_THROW_ON_ERROR
        );

        return Normalizer::normalizeTweets(
            [$statusDocument],
            function ($properties) use ($status) {
                return array_merge(
                    $properties,
                    [
                        'identifier' => $status->getIdentifier(),
                        'api_document' => $status->getApiDocument(),
                    ]
                );
            },
            $this->appLogger
        )->first()->toLegacyProps();
    }

    public function getAlias()
    {
        return 'archived_status';
    }

    public function getIdsOfExtremeStatusesSavedForMemberHavingScreenName(string $memberName): array
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $memberName]);

        return [
            'min_id' => $member->minTweetId(),
            'max_id' => $member->maxTweetId()
        ];
    }

    /**
     * @throws Exception
     */
    public function hasBeenSavedBefore(array $statuses): bool
    {
        if (count($statuses) === 0) {
            throw new Exception('There should be one item at least');
        }

        $identifier         = '';
        $statusesProperties = Normalizer::normalizeTweets(
            [$statuses[0]],
            function ($extract) use ($identifier) {
                $extract['identifier'] = $identifier;

                return $extract;
            },
            $this->appLogger
        );

        return $this->taggedTweetRepository->archivedStatusHavingHashExists(
            $statusesProperties->first()->hash()
        );
    }

    public function reviseDocument(TaggedTweet $taggedTweet): TweetInterface
    {
        /** @var Tweet $status */
        $status = $this->findOneBy(
            ['statusId' => $taggedTweet->documentId()]
        );

        $status->setApiDocument($taggedTweet->document());
        $status->setIdentifier($taggedTweet->token());
        $status->setText($taggedTweet->text());

        return $status->setUpdatedAt(
            new DateTime(
                'now',
                new \DateTimeZone('UTC')
            )
        );
    }

    public function saveLikes(
        array $statuses,
              $identifier,
        ?PublishersList $aggregate,
        LoggerInterface $logger,
        MemberInterface $likedBy,
        callable $ensureMemberExists
    ): CollectionInterface {
        $this->appLogger = $logger;

        $statusIds = $this->getStatusIdentities($statuses);

        $indexedStatus = $this->createIndexOfExistingStatus($statusIds);

        $extracts = Normalizer::normalizeTweets(
            $statuses,
            function ($extract) use ($identifier, $indexedStatus) {
                $extract['identifier'] = $identifier;

                return $extract;
            },
            $this->appLogger
        );

        $entityManager = $this->getEntityManager();

        /** @var TaggedTweet $taggedTweet */
        foreach ($extracts->toArray() as $key => $taggedTweet) {
            $extract      = $taggedTweet->toLegacyProps();

            $memberStatus = null;
            if (array_key_exists($taggedTweet->documentId(), $indexedStatus)) {
                $memberStatus = $indexedStatus[$taggedTweet->documentId()];
            }

            if (!($memberStatus instanceof TweetInterface)) {
                $memberStatus = $this->taggedTweetRepository
                    ->convertPropsToStatus($extract, $aggregate);
            }

            if ($memberStatus->getId() === null) {
                $this->collectStatusLogger->logStatus($memberStatus);
            }

            if ($memberStatus instanceof ArchivedTweet) {
                $memberStatus = $this->tweetPersistenceLayer->unarchiveStatus(
                    $memberStatus,
                    $entityManager
                );
            }

            try {
                if ($memberStatus->getId()) {
                    $memberStatus->setUpdatedAt(
                        new DateTime(
                            'now',
                            new \DateTimeZone('UTC')
                        )
                    );
                }

                try {
                    $member = $ensureMemberExists($memberStatus->getScreenName());
                } catch (ProtectedAccountException|SuspendedAccountException|NotFoundMemberException $exception) {
                    $member =
                        $this->memberRepository->findOneBy(['twitter_username' => $memberStatus->getScreenName()]);
                }

                if ($member instanceof MemberInterface) {
                    $entityManager->persist($memberStatus);
                }
            } catch (ORMException $exception) {
                if ($exception->getMessage() === ORMException::entityManagerClosed()->getMessage()) {
                    $entityManager = $this->registry->resetManager('default');
                    $entityManager->persist($memberStatus);
                }
            } catch (Exception $exception) {
                $this->appLogger->critical($exception->getMessage());
                continue;
            }
        }

        $this->flushAndResetManagerOnUniqueConstraintViolation($entityManager);

        if (count($extracts) > 0) {
            $this->memberRepository->incrementTotalLikesOfMemberWithName(
                count($extracts),
                $likedBy->twitterScreenName()
            );
        }

        return $extracts;
    }

    /**
     * @param array $statusIds
     *
     * @return array
     */
    private function createIndexOfExistingStatus(array $statusIds): array
    {
        $indexedStatuses = [];

        if (count($statusIds) > 0) {
            $queryBuilder = $this->createQueryBuilder('s');
            $queryBuilder->andWhere('s.statusId in (:ids)');
            $queryBuilder->setParameter('ids', $statusIds);
            $existingStatuses = $queryBuilder->getQuery()->getResult();

            array_walk(
                $existingStatuses,
                function (TweetInterface $existingStatus) use (&$indexedStatuses) {
                    $indexedStatuses[$existingStatus->getStatusId()] = $existingStatus;
                }
            );
        }

        return $indexedStatuses;
    }

    private function flushAndResetManagerOnUniqueConstraintViolation(
        EntityManagerInterface $entityManager
    ): void {
        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            $this->registry->resetManager('default');

            InsertDuplicatesException::throws($exception);
        }
    }

    private function getStatusIdentities(array $statuses): array
    {
        return array_map(
            function ($status) {
                return $status->id_str;
            },
            $statuses
        );
    }

    public function mapStatusCollectionToService(
        MappingAwareInterface $service,
        ArrayCollection $statuses
    ): iterable {
        return $statuses->map(function (Tweet $status) use ($service) {
            return $service->apply($status);
        });
    }

    public function persistSearchBasedTweetsCollection(
        AccessToken $identifier,
        SavedSearch $savedSearch,
        array $rawTweets,
    ): CollectionInterface {
        return $this->persistenceLayer->persistSearchBasedTweetsCollection(
            $identifier,
            $savedSearch,
            $rawTweets
        );
    }

    public function queryPublicationCollection(
        string $memberScreenName,
        DateTimeInterface $earliestDate,
        DateTimeInterface $latestDate
    ) {
        $queryBuilder = $this->createQueryBuilder('s');

        $this->between($queryBuilder, $earliestDate, $latestDate);

        $queryBuilder->andWhere('s.screenName = :screen_name');
        $queryBuilder->setParameter('screen_name', $memberScreenName);

        return new ArrayCollection($queryBuilder->getQuery()->getResult());
    }
}

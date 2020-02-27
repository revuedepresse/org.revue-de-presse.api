<?php
declare(strict_types=1);

namespace App\Api\Repository;

use App\Aggregate\Repository\TimelyStatusRepository;
use App\Api\AccessToken\AccessToken;
use App\Api\Adapter\StatusToArray;
use App\Api\Entity\Aggregate;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Exception\InsertDuplicatesException;
use App\Domain\Repository\StatusRepositoryInterface;
use App\Domain\Status\StatusInterface;
use App\Domain\Status\TaggedStatus;
use App\Infrastructure\DependencyInjection\PublicationRepositoryTrait;
use App\Infrastructure\DependencyInjection\StatusLoggerTrait;
use App\Infrastructure\DependencyInjection\StatusPersistenceTrait;
use App\Infrastructure\DependencyInjection\TaggedStatusRepositoryTrait;
use App\Infrastructure\Repository\Membership\MemberRepository;
use App\Infrastructure\Twitter\Api\Normalizer\Normalizer;
use App\Membership\Entity\MemberInterface;
use App\Operation\Collection\Collection;
use App\Operation\Collection\CollectionInterface;
use App\Status\Entity\LikedStatus;
use App\Status\Repository\ExtremumAwareInterface;
use App\Status\Repository\LikedStatusRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function count;
use const JSON_THROW_ON_ERROR;

/**
 * @package App\Api\Repository
 */
class ArchivedStatusRepository extends ResourceRepository implements ExtremumAwareInterface,
    StatusRepositoryInterface
{
    use PublicationRepositoryTrait;
    use StatusLoggerTrait;
    use StatusPersistenceTrait;
    use TaggedStatusRepositoryTrait;

    public ManagerRegistry $registry;

    public LoggerInterface $appLogger;

    public MemberRepository $memberManager;

    public TimelyStatusRepository $timelyStatusRepository;

    public LikedStatusRepository $likedStatusRepository;

    public Connection $connection;

    public bool $shouldExtractProperties;

    protected array $oauthTokens;

    /**
     * @param $screenName
     * @param $maxId
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function countCollectedStatuses($screenName, $maxId)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.hash) as count_')
                     ->andWhere('s.screenName = :screenName');

        if ($maxId < INF) {
            $queryBuilder->andWhere('(s.statusId + 0) <= :maxId');
        }

        $queryBuilder->setParameter('screenName', $screenName);

        if ($maxId < INF) {
            $queryBuilder->setParameter('maxId', $maxId);
        }

        try {
            $singleResult = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            $singleResult = ['count_' => null];
        }

        return $singleResult['count_'];
    }

    /**
     * @param $screenName
     *
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countHowManyStatusesFor($screenName): int
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
                     ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();

        return (int) $totalStatuses;
    }

    /**
     * @param null $lastId
     * @param null $aggregateName
     * @param bool $rawSql
     *
     * @return mixed
     */
    public function findLatest(
        $lastId = null,
        $aggregateName = null,
        $rawSql = false
    ) {
        if ($rawSql) {
            return $this->findLatestForAggregate($aggregateName);
        }

        $queryBuilder = $this->selectStatuses();

        if ($lastId !== null) {
            $queryBuilder->andWhere('t.id < :lastId');
            $queryBuilder->setParameter('lastId', $lastId);
        }

        if ($aggregateName !== null) {
            $queryBuilder->join(
                't.aggregates',
                'a'
            );
            $queryBuilder->andWhere('a.name = :aggregate_name');
            $queryBuilder->andWhere('a.screenName IS NOT NULL');
            $queryBuilder->setParameter('aggregate_name', $aggregateName);
        }

        $statuses = $queryBuilder->getQuery()->getResult();

        return $this->highlightRetweets($statuses);
    }

    public function findLatestForAggregate($aggregateName = null)
    {
        $queryTemplate = <<<QUERY
            SELECT
            `status`.ust_avatar AS author_avatar,
            `status`.ust_text AS text,
            `status`.ust_full_name AS screen_name,
            `status`.ust_id AS id,
            `status`.ust_status_id AS status_id,
            `status`.ust_starred AS starred,
            `status`.ust_api_document AS original_document,
            `status`.ust_created_at AS publication_date
            FROM :timely_status_table `timely_status`, :status_table `status`
            WHERE `timely_status`.aggregate_name = ':aggregate'
            AND `timely_status`.status_id = `status`.ust_id
            ORDER BY `timely_status`.time_range ASC, `timely_status`.publication_date_time DESC
            LIMIT :max_results
        ;
QUERY;

        $query = strtr(
            $queryTemplate,
            [
                ':aggregate'           => $aggregateName,
                ':max_results'         => 50,
                ':status_table'        => 'weaving_status',
                ':timely_status_table' => 'timely_status',
                ':aggregate_table'     => 'weaving_aggregate',
            ]
        );

        $statement = $this->connection->executeQuery($query);

        $statuses = $statement->fetchAll();

        return $this->highlightRetweets($statuses);
    }

    public function findLikedStatuses($aggregateName = null)
    {
        $queryTemplate = <<<QUERY
            SELECT
            `status`.ust_avatar AS author_avatar,
            `status`.ust_text AS text,
            `status`.ust_full_name AS screen_name,
            `status`.ust_id AS id,
            `status`.ust_status_id AS status_id,
            `status`.ust_starred AS starred,
            `status`.ust_api_document AS original_document,
            `status`.ust_created_at AS publication_date,
            `liked_status`.liked_by_member_name AS liked_by
            FROM :liked_status `liked_status`, :status_table `status`
            WHERE `liked_status`.status_id = `status`.ust_id
            ORDER BY `liked_status`.time_range ASC, `liked_status`.publication_date_time DESC
            LIMIT :max_results
        ;
QUERY;

        $query = strtr(
            $queryTemplate,
            [
                ':aggregate'       => $aggregateName,
                ':max_results'     => 500,
                ':status_table'    => 'weaving_status',
                ':liked_status'    => 'liked_status',
                ':aggregate_table' => 'weaving_aggregate',
            ]
        );

        $statement = $this->connection->executeQuery($query);

        $statuses = $statement->fetchAll();

        return $this->highlightRetweets($statuses);
    }

    /**
     * @param string        $screenName
     * @param DateTime|null $before
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findLocalMaximum(string $screenName, DateTime $before = null): array
    {
        $direction = 'asc';
        if (is_null($before)) {
            $direction = 'desc';
        }

        return $this->findNextExtremum($screenName, $direction, $before);
    }

    /**
     * @param string        $screenName
     * @param string        $direction
     * @param DateTime|null $before
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextExtremum(
        string $screenName,
        string $direction = 'asc',
        DateTime $before = null
    ): array {
        $member = $this->memberManager->findOneBy(['twitter_username' => $screenName]);
        if ($member instanceof MemberInterface) {
            if ($direction = 'desc' && !is_null($member->maxStatusId)) {
                return ['statusId' => $member->maxStatusId];
            }

            if ($direction = 'asc' && !is_null($member->minStatusId)) {
                return ['statusId' => $member->minStatusId];
            }
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
                     ->andWhere('s.screenName = :screenName')
                     ->andWhere('s.apiDocument is not null')
                     ->orderBy('s.statusId + 0', $direction)
                     ->setMaxResults(1);

        $queryBuilder->setParameter('screenName', $screenName);

        if ($before) {
            $queryBuilder->andWhere('DATE(s.createdAt) = :date');
            $queryBuilder->setParameter(
                'date',
                (new DateTime($before, new \DateTimeZone('UTC')))
                    ->format('Y-m-d')
            );
        }

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            if ($direction == 'asc') {
                return ['statusId' => +INF];
            }

            return ['statusId' => -INF];
        }
    }

    /**
     * @param string $screenName
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextMaximum(string $screenName): array
    {
        return $this->findNextExtremum($screenName, 'asc');
    }

    /**
     * @param string $screenName
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextMininum(string $screenName): array
    {
        return $this->findNextExtremum($screenName, 'desc');
    }

    /**
     * @param string $id
     *
     * @return array|StatusInterface|TaggedStatus
     * @throws Exception
     */
    public function findStatusIdentifiedBy(string $id)
    {
        $status = $this->findOneBy(['statusId' => $id]);
        if (!($status instanceof StatusInterface)) {
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

        return Normalizer::normalizeAll(
            [$statusDocument],
            function ($properties) {
                return $properties;
            },
            $this->appLogger
        )->first()->toLegacyProps();
    }

    public function getAlias()
    {
        return 'archived_status';
    }

    /**
     * @param string $memberName
     *
     * @return array
     */
    public function getIdsOfExtremeStatusesSavedForMemberHavingScreenName(string $memberName): array
    {
        $member = $this->memberManager->findOneBy(['twitter_username' => $memberName]);

        return [
            'min_id' => $member->minStatusId,
            'max_id' => $member->maxStatusId
        ];
    }

    /**
     * @param array $statuses
     *
     * @return bool
     * @throws Exception
     */
    public function hasBeenSavedBefore(array $statuses): bool
    {
        if (count($statuses) === 0) {
            throw new \Exception('There should be one item at least');
        }

        $identifier         = '';
        $statusesProperties = Normalizer::normalizeAll(
            [$statuses[0]],
            function ($extract) use ($identifier) {
                $extract['identifier'] = $identifier;

                return $extract;
            },
            $this->appLogger
        );

        return $this->taggedStatusRepository->archivedStatusHavingHashExists(
            $statusesProperties->first()->hash()
        );
    }

    public function reviseDocument(TaggedStatus $taggedStatus): StatusInterface
    {
        /** @var Status $status */
        $status = $this->findOneBy(
            ['statusId' => $taggedStatus->documentId()]
        );

        $status->setApiDocument($taggedStatus->document());
        $status->setIdentifier($taggedStatus->token());
        $status->setText($taggedStatus->text());

        return $status->setUpdatedAt(
            new DateTime(
                'now',
                new \DateTimeZone('UTC')
            )
        );
    }

    /**
     * @param array           $statuses
     * @param                 $identifier
     * @param Aggregate       $aggregate
     * @param LoggerInterface $logger
     * @param MemberInterface $likedBy
     * @param callable        $ensureMemberExists
     *
     * @return array
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveLikes(
        array $statuses,
        $identifier,
        ?Aggregate $aggregate,
        LoggerInterface $logger,
        MemberInterface $likedBy,
        callable $ensureMemberExists
    ): array {
        $this->appLogger = $logger;

        $entityManager = $this->getEntityManager();

        $statusIds = array_map(
            function ($status) {
                return $status->id_str;
            },
            $statuses
        );

        $indexedStatuses = [];
        if (count($statusIds) > 0) {
            $queryBuilder = $this->createQueryBuilder('s');
            $queryBuilder->andWhere('s.statusId in (:ids)');
            $queryBuilder->setParameter('ids', $statusIds);
            $existingStatuses = $queryBuilder->getQuery()->getResult();

            array_walk(
                $existingStatuses,
                function (StatusInterface $existingStatus) use (&$indexedStatuses) {
                    $indexedStatuses[$existingStatus->getStatusId()] = $existingStatus;
                }
            );
        }

        $extracts = Normalizer::normalizeAll(
            $statuses,
            function ($extract) use ($identifier, $indexedStatuses) {
                $extract['identifier'] = $identifier;

                $extract['existing_status'] = null;
                if (array_key_exists($extract['status_id'], $indexedStatuses)) {
                    $extract['existing_status'] = $indexedStatuses[$extract['status_id']];
                }

                return $extract;
            },
            $this->appLogger
        );

        $likedStatuses = [];

        foreach ($extracts->toArray() as $key => $taggedStatus) {
            $extract      = $taggedStatus->toLegacyProps();
            $memberStatus = $extract['existing_status'];
            if (!$memberStatus) {
                $memberStatus = $this->taggedStatusRepository
                    ->convertPropsToStatus($extract, $aggregate);
            }

            if ($memberStatus->getId() === null) {
                $this->collectStatusLogger->logStatus($memberStatus);
            }

            if ($memberStatus instanceof ArchivedStatus) {
                $memberStatus = $this->unarchiveStatus($memberStatus, $entityManager);
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
                    $member = $this->memberManager->findOneBy(['twitter_username' => $memberStatus->getScreenName()]);
                }

                if ($member instanceof MemberInterface) {
                    $likedStatuses[] = $this->likedStatusRepository->ensureMemberStatusHasBeenMarkedAsLikedBy(
                        $member,
                        $memberStatus,
                        $likedBy,
                        $aggregate
                    );

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
        $this->flushLikedStatuses($likedStatuses, $entityManager);

        if (count($extracts) > 0) {
            $this->memberManager->incrementTotalLikesOfMemberWithName(
                count($extracts),
                $likedBy->getTwitterUsername()
            );
        }

        return $extracts;
    }

    /**
     * @param array          $statuses
     * @param AccessToken    $identifier
     * @param Aggregate|null $aggregate
     *
     * @return CollectionInterface
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveStatuses(
        array $statuses,
        AccessToken $identifier,
        Aggregate $aggregate = null
    ): CollectionInterface {
        $result           = $this->statusPersistence->persistAllStatuses(
            $statuses,
            $identifier,
            $aggregate
        );
        $normalizedStatus = $result['extracts'];
        $screenName       = $result['screen_name'];

        // Mark status as published
        $statusCollection = new Collection($result['statuses']);
        $statusCollection->map(fn(StatusInterface $status) => $status->markAsPublished());

        // Make publications
        $statusCollection = StatusToArray::fromStatusCollection($statusCollection);
        $this->publicationRepository->persistPublications($statusCollection);

        // Commit transaction
        $this->getEntityManager()->flush();

        if (count($normalizedStatus) > 0) {
            $this->memberManager->incrementTotalStatusesOfMemberWithName(
                count($normalizedStatus),
                $screenName
            );
        }

        return $normalizedStatus;
    }

    public function setOauthTokens($oauthTokens)
    {
        $this->oauthTokens = $oauthTokens;

        return $this;
    }

    /**
     * @param array $statuses
     *
     * @return mixed
     */
    protected function highlightRetweets(array $statuses)
    {
        array_walk(
            $statuses,
            function (&$status) {
                $target = sprintf('user stream of id #%d', (int) $status['id']);
                if (strlen($status['original_document']) > 0) {
                    $decodedValue  = json_decode($status['original_document'], true);
                    $lastJsonError = json_last_error();
                    if (JSON_ERROR_NONE === $lastJsonError) {
                        if (array_key_exists('retweeted_status', $decodedValue)) {
                            $status['text'] = 'RT @' . $decodedValue['retweeted_status']['user']['screen_name'] . ': ' .
                                $decodedValue['retweeted_status']['full_text'];
                        }
                    } else {
                        throw new Exception(sprintf($lastJsonError . ' affecting ' . $target));
                    }
                } else {
                    throw new Exception(sprintf('Empty JSON document for ' . $target));
                }
            }
        );

        return $statuses;
    }

    /**
     * @param bool $lastWeek
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function selectStatuses()
    {
        $queryBuilder = $this->timelyStatusRepository->selectStatuses();

        if (!empty($this->oauthTokens)) {
            $queryBuilder->andWhere('s.identifier IN (:identifier)');
            $queryBuilder->setParameter('identifier', $this->oauthTokens);
        }

        return $queryBuilder;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
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

    /**
     * @param                        $likedStatuses
     * @param EntityManagerInterface $entityManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function flushLikedStatuses(
        $likedStatuses,
        EntityManagerInterface $entityManager
    ): void {
        (new ArrayCollection($likedStatuses))->map(
            function (LikedStatus $likedStatus) use ($entityManager) {
                $entityManager->persist($likedStatus);
            }
        );

        $this->flushAndResetManagerOnUniqueConstraintViolation(
            $entityManager
        );
    }
}

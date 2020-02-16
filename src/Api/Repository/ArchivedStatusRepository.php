<?php
declare(strict_types=1);

namespace App\Api\Repository;

use App\Aggregate\Repository\TimelyStatusRepository;
use App\Api\Adapter\StatusToArray;
use App\Membership\Entity\MemberInterface;
use App\Operation\Collection\Collection;
use App\Status\Entity\LikedStatus;
use App\Status\Repository\ExtremumAwareInterface;
use App\Status\Repository\LikedStatusRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

use App\Api\Entity\Aggregate;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Entity\StatusInterface;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Membership\Repository\MemberRepository;
use App\Twitter\Repository\PublicationRepositoryInterface;

use function count;

/**
 * @package App\Api\Repository
 */
class ArchivedStatusRepository extends ResourceRepository implements ExtremumAwareInterface
{
    protected array $oauthTokens;

    protected LoggerInterface $logger;

    public Registry $registry;

    public LoggerInterface $statusLogger;

    public MemberRepository $memberManager;

    public bool $shouldExtractProperties;

    public TimelyStatusRepository $timelyStatusRepository;

    public LikedStatusRepository $likedStatusRepository;

    public Connection $connection;

    protected PublicationRepositoryInterface $publicationRepository;

    public function setPublicationRepository(PublicationRepositoryInterface $publicationRepository)
    {
        $this->publicationRepository = $publicationRepository;
    }

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $aggregateClass
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        string $aggregateClass
    )
    {
        parent::__construct($managerRegistry, $aggregateClass);
    }

    public function setOauthTokens($oauthTokens)
    {
        $this->oauthTokens = $oauthTokens;

        return $this;
    }

    public function getAlias()
    {
        return 'archived_status';
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
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveLikes(
        array $statuses,
        $identifier,
        Aggregate $aggregate,
        LoggerInterface $logger,
        MemberInterface $likedBy,
        callable $ensureMemberExists
    ): array {
        $this->logger = $logger;

        $entityManager = $this->getEntityManager();

        $statusIds = array_map(
            function ($status) {
                return $status->id_str;
            }, $statuses
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

        $extracts = $this->extractProperties(
            $statuses,
            function ($extract) use ($identifier, $indexedStatuses) {
                $extract['identifier'] = $identifier;

                $extract['existing_status'] = null;
                if (array_key_exists($extract['status_id'], $indexedStatuses)) {
                    $extract['existing_status'] = $indexedStatuses[$extract['status_id']];
                }

                return $extract;
            }
        );

        $likedStatuses = [];

        foreach ($extracts as $key => $extract) {
            $memberStatus = $extract['existing_status'];
            if (!$memberStatus) {
                $memberStatus = $this->makeStatusFromApiResponseForAggregate($extract, $aggregate);
            }

            if ($memberStatus->getId() === null) {
                $this->logStatus($memberStatus);
            }

            if ($memberStatus->getId()) {
                unset($extracts[$key]);
            }

            if ($memberStatus instanceof ArchivedStatus) {
                $memberStatus = $this->unarchiveStatus($memberStatus, $entityManager);
            }

            try {
                if ($memberStatus->getId()) {
                    $memberStatus->setUpdatedAt(
                        new \DateTime(
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
                $this->logger->critical($exception->getMessage());
                continue;
            }
        }

        $this->flushStatuses($entityManager);
        $this->flushLikedStatuses($likedStatuses);

        if (count($extracts) > 0) {
            $this->memberManager->incrementTotalLikesOfMemberWithName(
                count($extracts),
                $likedBy->getTwitterUsername()
            );
        }

        return $extracts;
    }

    /**
     * @param                      $statuses
     * @param                      $identifier
     * @param Aggregate|null       $aggregate
     * @param LoggerInterface|null $logger
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function saveStatuses(
        $statuses,
        $identifier,
        Aggregate $aggregate = null,
        LoggerInterface $logger = null
    ) {
        $result = $this->iterateOverStatuses($statuses, $identifier, $aggregate, $logger);
        $extracts = $result['extracts'];
        $screenName = $result['screen_name'];

        $statuses = StatusToArray::fromStatusCollection($result['statuses']);
        $this->publicationRepository->persistPublications(
            Collection::fromArray($statuses)
        );

        $this->getEntityManager()->flush();

        if (count($extracts) > 0) {
            $this->memberManager->incrementTotalStatusesOfMemberWithName(
                count($extracts),
                $screenName
            );
        }

        return $extracts;
    }

    /**
     * @param EntityManager $entityManager
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function flushStatuses(EntityManager $entityManager): void
    {
        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            $entityManager = $this->registry->resetManager('default');

            throw new Exception(
                'Can not insert duplicates into the database',
                0,
                $exception
            );
        }
    }

    /**
     * @param string $id
     *
     * @return array
     * @throws Exception
     */
    public function findStatusIdentifiedBy(string $id): array
    {
        $status = $this->findOneBy(['statusId' => $id]);
        if (!($status instanceof StatusInterface)) {
            return [];
        }

        if (!$this->shouldExtractProperties) {
            return $status;
        }

        $statusDocument = json_decode($status->getApiDocument());

        return $this->extractProperties([$statusDocument], function ($properties) { return $properties; })[0];
    }

    /**
     * @param array    $statuses
     * @param callable $setter
     *
     * @return array
     * @throws Exception
     */
    protected function extractProperties(array $statuses, callable $setter): array
    {
        $extracts = [];

        foreach ($statuses as $status) {
            if (property_exists($status, 'text') || property_exists($status, 'full_text')) {
                $text = isset($status->full_text) ? $status->full_text : $status->text;

                $extract = [
                    'hash' => sha1($text . $status->id_str),
                    'text' => $text,
                    'screen_name' => $status->user->screen_name,
                    'name' => $status->user->name,
                    'user_avatar' => $status->user->profile_image_url,
                    'status_id' => $status->id_str,
                    'api_document' => json_encode($status),
                    'created_at' => new \DateTime($status->created_at),
                ];
                $extract = $setter($extract);
                $extracts[] = $extract;
            }
        }

        return $extracts;
    }

    /**
     * @param array $statuses
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function hasBeenSavedBefore(array $statuses): bool
    {
        if (count($statuses) === 0) {
            throw new Exception('There should be one item at least');
        }

        $identifier = '';
        $statusesProperties = $this->extractProperties([$statuses[0]], function ($extract) use ($identifier) {
            $extract['identifier'] = $identifier;

            return $extract;
        });

        return $this->existsAlready($statusesProperties[0]['hash']);
    }

    /**
     * @param $hash
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function existsAlready($hash)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
            ->andWhere('s.hash = :hash');

        $queryBuilder->setParameter('hash', $hash);
        $count = (int) $queryBuilder->getQuery()->getSingleScalarResult();

        $this->statusLogger->info(
            sprintf(
                '%d statuses already serialized for "%s"',
                $count,
                $hash
            )
        );

        return $count > 0;
    }

    /**
     * @param $screenName
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @deprecated in favor of ->countHowManyStatusesFor
     */
    public function countStatuses($screenName)
    {
        return $this->countHowManyStatusesFor($screenName);
    }

    /**
     * @param $screenName
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countHowManyStatusesFor($screenName)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();

        return (int) $totalStatuses;
    }

    /**
     * @param string $screenName
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextMaximum(string $screenName): array
    {
        return $this->findNextExtremum($screenName, 'asc');
    }

    /**
     * @param string $screenName
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextMininum(string $screenName): array
    {
        return $this->findNextExtremum($screenName, 'desc');
    }

    /**
     * @param string         $screenName
     * @param \DateTime|null $before
     * @return array
     * @throws NonUniqueResultException
     */
    public function findLocalMaximum(string $screenName, \DateTime $before = null): array
    {
        $direction = 'asc';
        if (is_null($before)) {
            $direction = 'desc';
        }

        return $this->findNextExtremum($screenName, $direction, $before);
    }

    /**
     * @param string         $screenName
     * @param string         $direction
     * @param \DateTime|null $before
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextExtremum(
        string $screenName,
        string $direction = 'asc',
        \DateTime $before = null
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
                (new \DateTime($before, new \DateTimeZone('UTC')))
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
     * @param $screenName
     * @param $maxId
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
     * @param null $lastId
     * @param null $aggregateName
     * @param bool $rawSql
     * @return mixed
     */
    public function findLatest($lastId = null, $aggregateName = null, $rawSql = false)
    {
        if ($rawSql) {
            return $this->findLatestForAggregate($aggregateName);
        }

        $queryBuilder = $this->selectStatuses();

        if (!is_null($lastId)) {
            $queryBuilder->andWhere('t.id < :lastId');
            $queryBuilder->setParameter('lastId', $lastId);
        }

        if (!is_null($aggregateName)) {
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
QUERY
;

        $query = strtr(
            $queryTemplate,
            [
                ':aggregate' => $aggregateName,
                ':max_results' => 500,
                ':status_table' => 'weaving_status',
                ':liked_status' => 'liked_status',
                ':aggregate_table' => 'weaving_aggregate',
            ]
        );

        $statement = $this->connection->executeQuery($query);

        $statuses = $statement->fetchAll();

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
QUERY
;

        $query = strtr(
            $queryTemplate,
            [
                ':aggregate' => $aggregateName,
                ':max_results' => 50,
                ':status_table' => 'weaving_status',
                ':timely_status_table' => 'timely_status',
                ':aggregate_table' => 'weaving_aggregate',
            ]
        );

        $statement = $this->connection->executeQuery($query);

        $statuses = $statement->fetchAll();

        return $this->highlightRetweets($statuses);
    }

    /**
     * @param bool $lastWeek
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
     * @param $statusIds
     * @return mixed
     */
    public function findBookmarks(array $statusIds)
    {
        if (count($statusIds) > 0) {
            $queryBuilder = $this->selectStatuses()
                ->andWhere('t.statusId IN (:statusIds)');
            $queryBuilder->setParameter('statusIds', $statusIds);

            $statuses = $queryBuilder->getQuery()->getResult();

            return $this->highlightRetweets($statuses);
        } else {
            return [];
        }
    }

    /**
     * @param array $statuses
     * @return mixed
     */
    protected function highlightRetweets(array $statuses)
    {
        array_walk(
            $statuses,
            function (&$status) {
                $target = sprintf('user stream of id #%d', (int) $status['id']);
                if (strlen($status['original_document']) > 0) {
                    $decodedValue = json_decode($status['original_document'], true);
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
     * @param                $extract
     * @param Aggregate|null $aggregate
     * @return StatusInterface
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function makeStatusFromApiResponseForAggregate($extract, Aggregate $aggregate = null): StatusInterface
    {
        $entityManager = $this->getEntityManager();

        /** @var \App\Api\Repository\StatusRepository $statusRepository */
        $statusRepository = $entityManager->getRepository('\App\Api\Entity\Status');

        if ($this->existsAlready($extract['hash'])) {
            $memberStatus = $statusRepository->updateResponseBody($extract);

            if ($this->logger) {
                $this->logger->info(
                    sprintf(
                        'Updating response body of status with hash "%s" for member with screen_name "%s"',
                        $extract['hash'],
                        $extract['screen_name']
                    )
                );
            }

            return $memberStatus;
        }

        /** @var \App\Api\Entity\Status $memberStatus */
        $memberStatus = $this->queryFactory->makeStatus($extract);
        $memberStatus->setIndexed(true);
        $memberStatus->setIdentifier($extract['identifier']);

        if (!is_null($aggregate)) {
            $memberStatus->addToAggregates($aggregate);
        }

        return $memberStatus;
    }

    /**
     * @param $memberStatus
     */
    private function logStatus(StatusInterface $memberStatus): void
    {
        $reach = $this->extractReachOfStatus($memberStatus);

        $favoriteCount = $reach['favorite_count'];
        $retweetCount = $reach['retweet_count'];

        $this->statusLogger->info(
            sprintf(
                '%s |_%s_| "%s" | @%s | %s | %s ',
                $memberStatus->getCreatedAt()->format('Y-m-d H:i'),
                str_pad($this->getStatusRelevance($retweetCount, $favoriteCount), 4, ' '),
                $this->getStatusAggregate($memberStatus),
                $memberStatus->getScreenName(),
                $memberStatus->getText(),
                'https://twitter.com/'.$memberStatus->getScreenName().'/status/'.$memberStatus->getStatusId()
            )
        );
    }

    /**
     * @param $retweetCount
     * @param $favoriteCount
     * @return string
     */
    private function getStatusRelevance($retweetCount, $favoriteCount): string
    {
        if ($retweetCount > 1000 || $favoriteCount > 1000) {
            return '!!!!';
        }

        if ($retweetCount > 100 || $favoriteCount > 100) {
            return '_!!!';
        }

        if ($retweetCount > 10 || $favoriteCount > 10) {
            return '__!!';
        }

        if ($retweetCount > 0 || $favoriteCount > 0) {
            return '___!';
        }

        return '____';
    }

    /**
     * @param ArchivedStatus $memberStatus
     * @param EntityManager  $entityManager
     * @return Status
     */
    private function unarchiveStatus(ArchivedStatus $memberStatus, EntityManager $entityManager): Status
    {
        $status = new Status();

        $status->setCreatedAt($memberStatus->getCreatedAt());
        $status->setApiDocument($memberStatus->getApiDocument());
        $status->setUpdatedAt($memberStatus->getUpdatedAt());
        $status->setText($memberStatus->getText());
        $status->setHash($memberStatus->getHash());
        $status->setIdentifier($memberStatus->getIdentifier());
        $status->setScreenName($memberStatus->getScreenName());
        $status->setStarred($memberStatus->isStarred());
        $status->setName($memberStatus->getName());
        $status->setIndexed($memberStatus->getIndexed());
        $status->setStatusId($memberStatus->getStatusId());
        $status->setUserAvatar($memberStatus->getUserAvatar());
        $status->setScreenName($memberStatus->getScreenName());

        if (!$memberStatus->getAggregates()->isEmpty()) {
            $memberStatus->getAggregates()->map(function (Aggregate $aggregate) use ($status) {
                $status->addToAggregates($aggregate);
            });
        }

        $entityManager->remove($memberStatus);

        $memberStatus = $status;

        return $memberStatus;
    }

    /**
     * @param StatusInterface $memberStatus
     * @return string
     */
    private function getStatusAggregate(StatusInterface $memberStatus): string
    {
        $aggregateName = 'without aggregate';
        if (!$memberStatus->getAggregates()->isEmpty()) {
            $aggregate = $memberStatus->getAggregates()->first();
            if ($aggregate instanceof Aggregate) {
                $aggregateName = $aggregate->getName();
            }
        }
        return $aggregateName;
    }

    /**
     * @param StatusInterface $memberStatus
     * @return array
     */
    public function extractReachOfStatus(StatusInterface $memberStatus): array
    {
        $decodedApiResponse = json_decode($memberStatus->getApiDocument(), true);

        $favoriteCount = 0;
        $retweetCount = 0;
        if (json_last_error() === JSON_ERROR_NONE) {
            if (array_key_exists('favorite_count', $decodedApiResponse)) {
                $favoriteCount = $decodedApiResponse['favorite_count'];
            }

            if (array_key_exists('retweet_count', $decodedApiResponse)) {
                $retweetCount = $decodedApiResponse['retweet_count'];
            }
        }

        return [
            'favorite_count' => $favoriteCount,
            'retweet_count' => $retweetCount
        ];
    }

    /**
     * @param string $memberName
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
     * @param $likedStatuses
     * @throws OptimisticLockException
     */
    private function flushLikedStatuses($likedStatuses): void
    {
        (new ArrayCollection($likedStatuses))->map(function (LikedStatus $likedStatus) {
            $this->getEntityManager()->persist($likedStatus);
        });
        $this->getEntityManager()->flush();
    }

    /**
     * @param                 $statuses
     * @param                 $identifier
     * @param Aggregate       $aggregate
     * @param LoggerInterface $logger
     *
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function iterateOverStatuses(
        $statuses,
        $identifier,
        Aggregate $aggregate = null,
        LoggerInterface $logger = null
    ): array {
        $this->logger = $logger;

        $entityManager = $this->getEntityManager();
        $extracts = $this->extractProperties($statuses, function ($extract) use ($identifier) {
            $extract['identifier'] = $identifier;

            return $extract;
        });

        $statuses = [];

        foreach ($extracts as $key => $extract) {
            $memberStatus = $this->makeStatusFromApiResponseForAggregate($extract, $aggregate);

            if ($memberStatus->getId() === null) {
                $this->logStatus($memberStatus);
            }

            if ($memberStatus->getId()) {
                unset($extracts[$key]);
            }

            if ($memberStatus instanceof ArchivedStatus) {
                $memberStatus = $this->unarchiveStatus($memberStatus, $entityManager);
            }

            try {
                if ($memberStatus->getId()) {
                    $memberStatus->setUpdatedAt(
                        new \DateTime(
                            'now',
                            new \DateTimeZone('UTC')
                        )
                    );
                }

                if ($aggregate instanceof Aggregate) {
                    $timelyStatus = $this->timelyStatusRepository->fromAggregatedStatus(
                        $memberStatus,
                        $aggregate
                    );
                    $entityManager->persist($timelyStatus);
                }

                $entityManager->persist($memberStatus);

                $statuses[] = $memberStatus;
            } catch (ORMException $exception) {
                if ($exception->getMessage() === ORMException::entityManagerClosed()->getMessage()) {
                    $entityManager = $this->registry->resetManager('default');
                    $entityManager->persist($memberStatus);
                }
            }
        }

        $this->flushStatuses($entityManager);

        return [
            'extracts' => $extracts,
            'screen_name' => $extract['screen_name'],
            'statuses' => $statuses
        ];
    }
}

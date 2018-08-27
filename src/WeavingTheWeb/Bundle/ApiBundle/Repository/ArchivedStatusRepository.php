<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\NoResultException;

use Doctrine\ORM\ORMException;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Psr\Log\LoggerInterface;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;
use WTW\UserBundle\Repository\UserRepository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ArchivedStatusRepository extends ResourceRepository
{
    /**
     * @var array
     */
    protected $oauthTokens;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Registry
     */
    public $registry;

    /**
     * @var LoggerInterface
     */
    public $statusLogger;

    /**
     * @var UserRepository
     */
    public $memberManager;

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
     * @param                      $statuses
     * @param                      $identifier
     * @param Aggregate|null       $aggregate
     * @param LoggerInterface|null $logger
     * @return array
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function saveStatuses(
        $statuses,
        $identifier,
        Aggregate $aggregate = null,
        LoggerInterface $logger = null
    ) {
        $this->logger = $logger;

        $entityManager = $this->getEntityManager();
        $extracts = $this->extractProperties($statuses, function ($extract) use ($identifier) {
            $extract['identifier'] = $identifier;

            return $extract;
        });

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

                $entityManager->persist($memberStatus);
            } catch (ORMException $exception) {
                if ($exception->getMessage() === ORMException::entityManagerClosed()->getMessage()) {
                    $entityManager = $this->registry->resetManager('default');
                    $entityManager->persist($memberStatus);
                }
            }
        }

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            $entityManager = $this->registry->resetManager('default');

            throw new \Exception('Can not insert duplicates into the database', 0, $exception);
        }

        if (count($extracts) > 0) {
            $this->memberManager->incrementTotalStatusesOfMemberWithScreenName(
                count($extracts),
                $extract['screen_name']
            );
        }

        return $extracts;
    }

    /**
     * @param $statuses
     * @param $setter
     * @return array
     */
    protected function extractProperties($statuses, callable $setter)
    {
        $extracts = [];

        foreach ($statuses as $status) {
            if (property_exists($status, 'text')) {
                $extract = [
                    'hash' => sha1($status->text . $status->id_str),
                    'text' => $status->text,
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
     * @param $hash
     * @return bool
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function existsAlready($hash)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
            ->andWhere('s.hash = :hash');

        $queryBuilder->setParameter('hash', $hash);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        if ($this->logger) {
            $this->logger->info(
                sprintf(
                    '%d statuses already serialized for "%s"',
                    $count,
                    $hash
                )
            );
        }

        return $count > 0;
    }

    /**
     * @param $screenName
     * @return int
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countHowManyStatusesFor($screenName)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();

        return intval($totalStatuses);
    }

    /**
     * @param $screenName
     * @return array|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findNextMaximum($screenName)
    {
        return $this->findNextExtremum($screenName, 'asc');
    }

    /**
     * @param $screenName
     * @return array|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findNextMininum($screenName)
    {
        return $this->findNextExtremum($screenName, 'desc');
    }

    /**
     * @param $screenName
     * @param $before
     * @return array|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLocalMaximum($screenName, $before)
    {
        return $this->findNextExtremum($screenName, 'asc', $before);
    }

    /**
     * @param        $screenName
     * @param string $direction
     * @param null   $before
     * @return array|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function findNextExtremum($screenName, $direction = 'asc', $before = null)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
            ->andWhere('s.screenName = :screenName')
            ->andWhere('s.apiDocument is not null')
            ->orderBy('s.statusId + 0', $direction)
            ->setMaxResults(1);

        $queryBuilder->setParameter('screenName', $screenName);

        if ($before) {
            $queryBuilder->andWhere('DATE(s.createdAt) = :date');
            $queryBuilder->setParameter('date', (new \DateTime($before))->format('Y-m-d'));
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countOlderStatuses($screenName, $maxId)
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

        $queryBuilder = $this->selectStatuses($lastWeek = true);

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

    public function findLatestForAggregate($aggregateName = null)
    {
        $queryTemplate = <<<QUERY
            SELECT
            ust_avatar AS author_avatar,
            ust_text AS text,
            ust_full_name AS screen_name,
            ust_id AS id,
            ust_status_id AS status_id, 
            ust_starred AS starred,
            ust_api_document AS original_document,
            ust_created_at AS publication_date
            FROM (
                SELECT `status`.*
                FROM :status_table `status`
                WHERE ust_id IN (
                    SELECT
                    status_aggregate.status_id
                    FROM :status_aggregate_table status_aggregate
                    INNER JOIN :aggregate_table aggregate ON (
                       aggregate.id = status_aggregate.aggregate_id AND  aggregate.screen_name IS NOT NULL
                       AND COALESCE(aggregate.name, '') = ':aggregate'
                    )
                    ORDER BY status_aggregate.status_id DESC
                )
                ORDER BY `status`.ust_created_at DESC
                LIMIT :max_results
            ) aggregated_statuses
            ORDER BY ust_created_at DESC
QUERY
;

        $query = strtr(
            $queryTemplate,
            [
                ':aggregate' => $aggregateName,
                ':max_results' => 10,
                ':status_table' => 'weaving_status',
                ':status_aggregate_table' => 'weaving_status_aggregate',
                ':aggregate_table' => 'weaving_aggregate'
            ]
        );

        $statement = $this->createQueryBuilder('t')
            ->getEntityManager()
            ->getConnection()->executeQuery($query);

        $statuses = $statement->fetchAll();

        return $this->highlightRetweets($statuses);
    }

    /**
     * @param bool $lastWeek
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function selectStatuses($lastWeek = true)
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->select(
            [
                't.userAvatar as author_avatar',
                't.text',
                't.screenName as screen_name',
                't.id',
                't.statusId as status_id',
                't.starred',
                't.apiDocument original_document'
            ]
        )
            ->orderBy('t.createdAt', 'desc')
            ->setMaxResults(300)
        ;

        if (!empty($this->oauthTokens)) {
            $queryBuilder->andWhere('t.identifier IN (:identifier)');
            $queryBuilder->setParameter('identifier', $this->oauthTokens);
        }

        if ($lastWeek) {
            $queryBuilder->andWhere('t.createdAt > :lastWeek');

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $lastWeek = $now->setTimestamp(strtotime('last week'));
            $queryBuilder->setParameter('lastWeek', $lastWeek);
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
                $target = sprintf('user stream of id #%d', intval($status['id']));
                if (strlen($status['original_document']) > 0) {
                    $decodedValue = json_decode($status['original_document'], true);
                    $lastJsonError = json_last_error();
                    if (JSON_ERROR_NONE === $lastJsonError) {
                        if (array_key_exists('retweeted_status', $decodedValue)) {
                            $status['text'] = 'RT @' . $decodedValue['retweeted_status']['user']['screen_name'] . ': ' .
                                $decodedValue['retweeted_status']['text'];
                        }
                    } else {
                        throw new \Exception(sprintf($lastJsonError . ' affecting ' . $target));
                    }
                } else {
                    throw new \Exception(sprintf('Empty JSON document for ' . $target));
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function makeStatusFromApiResponseForAggregate($extract, Aggregate $aggregate = null): StatusInterface
    {
        $entityManager = $this->getEntityManager();

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository $statusRepository */
        $statusRepository = $entityManager->getRepository('\WeavingTheWeb\Bundle\ApiBundle\Entity\Status');

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

        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Status $memberStatus */
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
}

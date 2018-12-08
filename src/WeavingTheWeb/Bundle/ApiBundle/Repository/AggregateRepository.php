<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use App\Aggregate\Controller\SearchParams;
use App\Aggregate\Entity\TimelyStatus;
use App\Aggregate\Repository\PaginationAwareTrait;
use App\Aggregate\Repository\TimelyStatusRepository;
use App\Member\MemberInterface;
use App\Operation\CapableOfDeletionInterface;
use App\Status\Entity\LikedStatus;
use App\Status\Repository\LikedStatusRepository;
use Doctrine\ORM\QueryBuilder;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AggregateRepository extends ResourceRepository implements CapableOfDeletionInterface
{
    const TABLE_ALIAS = 'a';

    use PaginationAwareTrait;

    /**
     * @var TimelyStatusRepository
     */
    public $timelyStatusRepository;

    /**
     * @var LikedStatusRepository
     */
    public $likedStatusRepository;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var Producer
     */
    public $amqpMessageProducer;

    /**
     * @var TokenRepository
     */
    public $tokenRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @param string $screenName
     * @param string $listName
     * @return null|object|Aggregate
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function make(string $screenName, string $listName)
    {
        $aggregate = $this->findByRemovingDuplicates(
            $screenName,
            $listName
        );

        if ($aggregate instanceof Aggregate) {
            return $aggregate;
        }

        return new Aggregate($screenName, $listName);
    }

    /**
     * @param MemberInterface $member
     * @param \stdClass       $list
     * @return Aggregate
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addMemberToList(
        MemberInterface $member,
        \stdClass $list
    ) {
        $aggregate = $this->findOneBy([
            'name' => $list->name,
            'screenName' => $member->getTwitterUsername()
        ]);

        if (!($aggregate instanceof Aggregate)) {
            $aggregate = $this->make($member->getTwitterUsername(), $list->name);
        }

        $aggregate->listId = $list->id_str;

        return $this->save($aggregate);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function selectAggregatesForWhichNoStatusHasBeenCollected(): array
    {
        $selectAggregates = <<<QUERY
            SELECT 
            a.id aggregate_id, 
            screen_name member_screen_name, 
            `name` aggregate_name,
            u.usr_twitter_id member_id
            FROM weaving_aggregate a, weaving_user u
            WHERE screen_name IS NOT NULL 
            AND a.screen_name = u.usr_twitter_username
            AND id NOT IN (
                SELECT aggregate_id FROM weaving_status_aggregate
            );
QUERY;

        $statement = $this->getEntityManager()->getConnection()->executeQuery($selectAggregates);

        return $statement->fetchAll();
    }

    /**
     * @param Aggregate $aggregate
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function lockAggregate(Aggregate $aggregate)
    {
        $aggregate->lock();

        $this->save($aggregate);
    }

    /**
     * @param Aggregate $aggregate
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unlockAggregate(Aggregate $aggregate)
    {
        $aggregate->unlock();

        $this->save($aggregate);
    }

    /**
     * @param Aggregate $aggregate
     * @return Aggregate
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Aggregate $aggregate)
    {
        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();

        return $aggregate;
    }

    /**
     * @param string $screenName
     * @param string $listName
     * @return null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function findByRemovingDuplicates(
        string $screenName,
        string $listName
    ) {
        $aggregate = $this->findOneBy([
            'screenName' => $screenName,
            'name' => $listName
        ]);

        if ($aggregate instanceof Aggregate) {
            $aggregates = $this->findBy([
                'screenName' => $screenName,
                'name' => $listName
            ]);

            if (count($aggregates) > 1) {
                foreach ($aggregates as $index => $aggregate) {
                    if ($index === 0) {
                        continue;
                    }

                    $statuses = $this->statusRepository
                        ->findByAggregate($aggregate);

                    /** @var StatusInterface $status */
                    foreach ($statuses as $status) {
                        /** @var StatusInterface $status */
                        $status->removeFrom($aggregate);
                        $status->addToAggregates($aggregates[0]);
                    }

                    $timelyStatuses = $this->timelyStatusRepository
                        ->findBy(['aggregate' => $aggregate]);

                    /** @var TimelyStatus $timelyStatus */
                    foreach ($timelyStatuses as $timelyStatus) {
                        $timelyStatus->updateAggregate($aggregates[0]);
                    }

                    $likedStatuses = $this->likedStatusRepository
                        ->findBy(['aggregate' => $aggregate]);

                    /** @var LikedStatus $likedStatus */
                    foreach ($likedStatuses as $likedStatus) {
                        $likedStatus->setAggregate($aggregates[0]);
                    }
                }

                $this->getEntityManager()->flush();

                foreach ($aggregates as $index => $aggregate) {
                    if ($index === 0) {
                        continue;
                    }

                    $this->getEntityManager()->remove($aggregate);
                }

                $this->getEntityManager()->flush();

                $aggregate = $aggregates[0];
            }
        }

        return $aggregate;
    }

    /**
     * @param SearchParams $searchParams
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @param SearchParams $searchParams
     * @return array
     */
    public function findAggregates(SearchParams $searchParams): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);

        $queryBuilder->select();
        $queryBuilder->distinct();
        $queryBuilder->groupBy('a.name');

        $this->applyCriteria($queryBuilder, $searchParams);
        
        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());
        $queryBuilder->setMaxResults($searchParams->getPageSize());
        $queryBuilder->orderBy('a.name', 'ASC');

        $aggregates = $queryBuilder->getQuery()->getArrayResult();

        $aggregates = array_map(
            function(array $aggregate) {
                $existingAggregate = null;
                if (($aggregate['totalStatuses'] === 0) ||
                    ($aggregate['totalMembers'] === 0 || true)) {
                    /** @var Aggregate $existingAggregate */
                    $existingAggregate = $this->findOneBy(['id' => $aggregate['id']]);
                    if (!($existingAggregate instanceof Aggregate)) {
                        return $aggregate;
                    }
                }

                $aggregate = $this->updateTotalStatuses(
                    $aggregate,
                    $existingAggregate
                );


                return $this->updateTotalMembers($aggregate, $existingAggregate);
            },
            $aggregates
        );

        try {
            $this->getEntityManager()->flush();
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
        }


        return $aggregates;
    }

    /**
     * @param array          $aggregate
     * @param Aggregate|null $matchingAggregate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateTotalStatusesByExcludingRelatedAggregates(
        array $aggregate,
        Aggregate $matchingAggregate = null
    ): array {
        return $this->updateTotalStatuses($aggregate, $matchingAggregate, $includeRelatedAggregates = false);
    }

    /**
     * @param array          $aggregate
     * @param Aggregate|null $matchingAggregate
     * @param bool           $includeRelatedAggregates
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateTotalStatuses(
        array $aggregate,
        Aggregate $matchingAggregate = null,
        bool $includeRelatedAggregates = true
    ): array {
        if ($aggregate['totalStatuses'] === 0 || true) {
            $connection = $this->getEntityManager()->getConnection();

            $query = <<< QUERY
                SELECT count(*) as total_statuses
                FROM timely_status
                WHERE aggregate_id = ?;
QUERY;

            if ($includeRelatedAggregates) {
                $query = <<< QUERY
                    SELECT count(*) as total_statuses
                    FROM timely_status
                    WHERE aggregate_id in (
                      SELECT am.id
                      FROM weaving_aggregate a
                      INNER JOIN weaving_aggregate am 
                      ON ( a.name = am.name )
                      WHERE a.id = ? 
                    );
QUERY;
            }

            $statement = $connection->executeQuery(
                $query,
                [$aggregate['id']],
                [\PDO::PARAM_INT]
            );

            $aggregate['totalStatuses'] = intval($statement->fetchAll()[0]['total_statuses']);

            $matchingAggregate->totalStatuses = $aggregate['totalStatuses'];
            if ($aggregate['totalStatuses'] === 0) {
                $matchingAggregate->totalStatuses = -1;
            }

            $this->getEntityManager()->persist($matchingAggregate);
        }

        return $aggregate;
    }

    /**
     * @param array          $aggregate
     * @param Aggregate|null $matchingAggregate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    private function updateTotalMembers(
        array $aggregate,
        Aggregate $matchingAggregate = null
    ): array {
        if ($aggregate['totalMembers'] === 0 || true) {
            $query = <<<QUERY
                SELECT 
                count(a.screen_name) as total_members
                FROM weaving_aggregate a
                WHERE screen_name IS NOT NULL
                AND name in (
                    SELECT a.name
                    FROM weaving_aggregate a
                    WHERE id = ?
                )
QUERY;
            $connection = $this->getEntityManager()->getConnection();
            $statement = $connection->executeQuery($query, [$aggregate['id']], [\Pdo::PARAM_INT]);

            $aggregate['totalMembers'] = intval($statement->fetchAll()[0]['total_members']);

            $matchingAggregate->totalMembers = $aggregate['totalMembers'];
            if ($aggregate['totalMembers'] === 0) {
                $matchingAggregate->totalMembers = -1;
            }

            $this->getEntityManager()->persist($matchingAggregate);

        }

        return $aggregate;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     */
    private function applyCriteria(QueryBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $queryBuilder->andWhere('a.name not like :name');
        $queryBuilder->setParameter('name', "user ::%");

        $queryBuilder = $this->excludeDeletedRecords($queryBuilder);

        if ($searchParams->hasKeyword()) {
            $queryBuilder->andWhere('a.name like :keyword');
            $queryBuilder->setParameter(
                'keyword',
                sprintf(
                    '%%%s%%',
                    strtr(
                        $searchParams->getKeyword(),
                        [
                            '_' => '\_',
                            '%' => '%%',
                        ]
                    )
                )
            );
        }
    }

    /**
     * @param array $aggregateIds
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function bulkRemoveAggregates(array $aggregateIds)
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $queryBuilder->andWhere(self::TABLE_ALIAS.'.id in (:aggregate_ids)');
        $queryBuilder->setParameter('aggregate_ids', $aggregateIds);

        $aggregates = $queryBuilder->getQuery()->getResult();
        array_walk(
            $aggregates,
            function (Aggregate $aggregate) {
                $aggregate->markAsDeleted();
            }
        );

        $this->getEntityManager()->flush();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder|mixed
     */
    public function excludeDeletedRecords(QueryBuilder $queryBuilder)
    {
        $queryBuilder->andWhere(self::TABLE_ALIAS.'.deletedAt IS NULL');

        return $queryBuilder;
    }

    /**
     * @param array $aggregateIds
     */
    public function publishStatusesForAggregates(array $aggregateIds)
    {
        $query = <<<QUERY
            SELECT id, screen_name
            FROM weaving_aggregate
            WHERE screen_name IS NOT NULL
            AND name in (
                SELECT name 
                FROM weaving_aggregate
                WHERE id in (:ids)
            )
QUERY;
        $connection = $this->getEntityManager()->getConnection();
        $aggregateIds = implode(
            array_map(
                'intval',
                $aggregateIds
            ),
            ','
        );

        try {
            $statement = $connection->executeQuery(
                strtr(
                    $query,
                    [
                        ':ids' => $aggregateIds
                    ]
                )
            );
            $records = $statement->fetchAll();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        $aggregateIds = array_map(
            function ($record) {
                return $record['id'];
            }, $records
        );
        $aggregates = $this->findBy(['id' => $aggregateIds]);

        array_walk(
            $aggregates,
            function (Aggregate $aggregate) {
                $messageBody['screen_name'] = $aggregate->screenName;
                $messageBody['aggregate_id'] = $aggregate->getId();
                $aggregate->totalStatuses = 0;
                $aggregate->totalMembers = 0;

                $this->save($aggregate);

                $this->amqpMessageProducer->setContentType('application/json');
                $this->amqpMessageProducer->publish(serialize(json_encode($messageBody)));
            }
        );
    }

    /**
     * @param array $aggregateIds
     */
    public function resetTotalStatusesForAggregates(array $aggregateIds)
    {
        $query = <<<QUERY
            UPDATE weaving_aggregate a
            SET total_statuses = 0
            WHERE id in (:ids)
QUERY;
        $connection = $this->getEntityManager()->getConnection();
        $aggregateIds = implode(
            array_map(
                'intval',
                $aggregateIds
            ),
            ','
        );

        try {
            $connection->executeQuery(
                strtr(
                    $query,
                    [
                        ':ids' => $aggregateIds
                    ]
                )
            );
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }
}

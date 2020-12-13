<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Repository;

use App\Twitter\Infrastructure\Http\SearchParams;
use App\PublishersList\Entity\TimelyStatus;
use App\PublishersList\Repository\PaginationAwareTrait;
use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListDispatcherTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Twitter\Domain\PublishersList\Repository\PublishersListRepositoryInterface;
use App\Membership\Domain\Entity\Legacy\Member;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Infrastructure\Operation\CapableOfDeletionInterface;
use App\Twitter\Domain\Curation\Entity\LikedStatus;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use function array_map;
use function array_sum;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @method PublishersListInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method PublishersListInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method PublishersListInterface[]    findAll()
 * @method PublishersListInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PublishersListRepository extends ResourceRepository implements CapableOfDeletionInterface,
    PublishersListRepositoryInterface
{
    use StatusRepositoryTrait;
    use LikedStatusRepositoryTrait;
    use LoggerTrait;
    use PaginationAwareTrait;
    use PublishersListDispatcherTrait;
    use TimelyStatusRepositoryTrait;
    use TokenRepositoryTrait;

    private const TABLE_ALIAS = 'a';

    private const PREFIX_MEMBER_AGGREGATE = 'user :: ';

    /**
     * @param MemberInterface $member
     * @param stdClass        $list
     *
     * @return PublishersListInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addMemberToList(
        MemberInterface $member,
        stdClass $list
    ): PublishersListInterface {
        $aggregate = $this->findOneBy(
            [
                'name'       => $list->name,
                'screenName' => $member->getTwitterUsername()
            ]
        );

        if (!($aggregate instanceof Aggregate)) {
            $aggregate = $this->make($member->getTwitterUsername(), $list->name);
        }

        $aggregate->listId = $list->id_str;

        return $this->save($aggregate);
    }

    /**
     * @param array $aggregateIds
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function bulkRemoveAggregates(array $aggregateIds)
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $queryBuilder->andWhere(self::TABLE_ALIAS . '.id in (:aggregate_ids)');
        $queryBuilder->setParameter('aggregate_ids', $aggregateIds);

        $aggregates = $queryBuilder->getQuery()->getResult();
        array_walk(
            $aggregates,
            function (PublishersListInterface $aggregate) {
                $aggregate->markAsDeleted();
            }
        );

        $this->getEntityManager()->flush();
    }

    /**
     * @param SearchParams $searchParams
     *
     * @return int
     * @throws NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder|mixed
     */
    public function excludeDeletedRecords(QueryBuilder $queryBuilder)
    {
        $queryBuilder->andWhere(self::TABLE_ALIAS . '.deletedAt IS NULL');

        return $queryBuilder;
    }

    /**
     * @param SearchParams $searchParams
     *
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
            function (array $aggregate) {
                $existingAggregate = null;
                if ($aggregate['totalStatuses'] === 0) {
                    /** @var PublishersListInterface $existingAggregate */
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
        } catch (Exception $exception) {
            $this->logger->critical($exception);
        }

        return $aggregates;
    }

    /**
     * @param string      $screenName
     * @param string      $listName
     * @param string|null $listId
     *
     * @return PublishersListInterface|object|null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getListAggregateByName(
        string $screenName,
        string $listName,
        string $listId = null
    ) {
        $aggregate = $this->make(
            $screenName,
            $listName
        );

        $this->getEntityManager()->persist($aggregate);

        if ($listId !== null) {
            $aggregate->listId = $listId;
        }

        $this->getEntityManager()->flush();

        return $aggregate;
    }

    /**
     * @param string $username
     *
     * @return PublishersListInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getMemberAggregateByUsername(string $username): PublishersListInterface
    {
        $aggregate = $this->make(
            $username,
            self::PREFIX_MEMBER_AGGREGATE . $username
        );

        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();

        return $aggregate;
    }

    /**
     * @param PublishersListInterface $aggregate
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function lockAggregate(PublishersListInterface $aggregate)
    {
        $aggregate->lock();

        $this->save($aggregate);
    }

    /**
     * @param string $screenName
     * @param string $listName
     *
     * @return PublishersListInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function make(string $screenName, string $listName): PublishersListInterface
    {
        $aggregate = $this->findByRemovingDuplicates(
            $screenName,
            $listName
        );

        if ($aggregate instanceof PublishersListInterface) {
            return $aggregate;
        }

        return new Aggregate($screenName, $listName);
    }

    /**
     * @param array $aggregateIds
     *
     * @return int
     */
    public function publishStatusesForAggregates(array $aggregateIds): int
    {
        $query        = <<<QUERY
            SELECT id, screen_name
            FROM weaving_aggregate
            WHERE screen_name IS NOT NULL
            AND name in (
                SELECT name 
                FROM weaving_aggregate
                WHERE id in (:ids)
            )
QUERY;
        $connection   = $this->getEntityManager()->getConnection();
        $aggregateIds = $this->castIds($aggregateIds);

        try {
            $statement = $connection->executeQuery(
                strtr(
                    $query,
                    [
                        ':ids' => implode(',', $aggregateIds)
                    ]
                )
            );
            $records   = $statement->fetchAll();
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $records = [];
        }

        $aggregateIds = array_map(
            function ($record) {
                return $record['id'];
            },
            $records
        );
        $aggregates   = $this->findBy(['id' => $aggregateIds]);

        $dispatchedMessages = array_map(
            function (PublishersListInterface $aggregate) {
                $messageBody['aggregate_id'] = $aggregate->getId();
                $aggregate->setTotalStatus(0);
                $aggregate->totalMembers     = 0;

                $this->save($aggregate);

                $token = $this->tokenRepository->findFirstUnfrozenToken();
                if ($token instanceof TokenInterface) {
                    $this->publishersListDispatcher->dispatchMemberPublishersListMessage(
                        (new Member())->setScreenName($aggregate->screenName),
                        $token
                    );

                    return 1;
                }

                return 0;
            },
            $aggregates
        );

        return array_sum($dispatchedMessages);
    }

    /**
     * @param string $memberName
     */
    public function resetTotalStatusesForAggregateRelatedToScreenName(string $memberName)
    {
        $aggregates = $this->findBy(['screenName' => $memberName]);
        array_walk(
            $aggregates,
            function (PublishersListInterface $aggregate) {
                $aggregate->totalStatuses = 0;

                $this->getEntityManager()->persist($aggregate);
                $this->getEntityManager()->flush();
            }
        );
    }

    /**
     * @param array $aggregateIds
     */
    public function resetTotalStatusesForAggregates(array $aggregateIds)
    {
        $query        = <<<QUERY
            UPDATE weaving_aggregate a
            SET total_statuses = 0
            WHERE id in (:ids)
QUERY;
        $connection   = $this->getEntityManager()->getConnection();
        $aggregateIds = $this->castIds($aggregateIds);
        $aggregateIdsParams = implode(
            ',',
            $aggregateIds
        );

        try {
            $connection->executeQuery(
                strtr(
                    $query,
                    [
                        ':ids' => $aggregateIdsParams
                    ]
                )
            );
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }

    /**
     * @param PublishersListInterface $aggregate
     *
     * @return PublishersListInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(PublishersListInterface $aggregate)
    {
        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();

        return $aggregate;
    }

    /**
     * @return array
     * @throws DBALException
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
     * @param PublishersListInterface $aggregate
     *
     * @return PublishersListInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function unlockPublishersList(PublishersListInterface $aggregate): PublishersListInterface
    {
        $aggregate->unlock();

        return $this->save($aggregate);
    }

    /**
     * @param array                         $aggregate
     * @param PublishersListInterface|null $matchingAggregate
     * @param bool                          $includeRelatedAggregates
     *
     * @return array
     * @throws DBALException
     * @throws ORMException
     */
    public function updateTotalStatuses(
        array $aggregate,
        ?PublishersListInterface $matchingAggregate = null,
        bool $includeRelatedAggregates = true
    ): array {
        if ($aggregate['totalStatuses'] <= 0) {
            $connection = $this->getEntityManager()->getConnection();

            $query = <<< QUERY
                SELECT count(*) as total_status
                FROM weaving_status_aggregate
                WHERE aggregate_id = ?;
QUERY;

            if ($includeRelatedAggregates) {
                $query = <<< QUERY
                    SELECT count(*) as total_status
                    FROM weaving_status_aggregate
                    WHERE aggregate_id in (
                      SELECT am.id
                      FROM weaving_aggregate a
                      INNER JOIN weaving_aggregate am 
                      ON ( a.screen_name = am.screen_name AND am.screen_name IS NOT NULL )
                      WHERE a.id = ? 
                    );
QUERY;
            }

            $statement = $connection->executeQuery(
                $query,
                [$aggregate['id']],
                [\PDO::PARAM_INT]
            );

            $aggregate['totalStatuses'] = (int) $statement->fetchAll()[0]['total_status'];

            $matchingAggregate->setTotalStatus($aggregate['totalStatuses']);
            if ($aggregate['totalStatuses'] === 0) {
                $matchingAggregate->setTotalStatus(-1);
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
     *
     * @return array
     */
    private function castIds(array $aggregateIds): array
    {
        return array_map(
            fn($aggregateId) => (int) $aggregateId,
            $aggregateIds
        );
    }

    /**
     * @param string $screenName
     * @param string $listName
     *
     * @return null|object
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function findByRemovingDuplicates(
        string $screenName,
        string $listName
    ) {
        $aggregate = $this->findOneBy(
            [
                'screenName' => $screenName,
                'name'       => $listName
            ]
        );

        if ($aggregate instanceof Aggregate) {
            $aggregates = $this->findBy(
                [
                    'screenName' => $screenName,
                    'name'       => $listName
                ]
            );

            if (\count($aggregates) > 1) {
                /** @var PublishersListInterface $firstAggregate */
                $firstAggregate = $aggregates[0];

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
                        $status->addToAggregates($firstAggregate);
                    }

                    $timelyStatuses = $this->timelyStatusRepository
                        ->findBy(['aggregate' => $aggregate]);

                    /** @var TimelyStatus $timelyStatus */
                    foreach ($timelyStatuses as $timelyStatus) {
                        $timelyStatus->updateAggregate($firstAggregate);
                    }

                    $likedStatuses = $this->likedStatusRepository
                        ->findBy(['aggregate' => $aggregate]);

                    /** @var LikedStatus $likedStatus */
                    foreach ($likedStatuses as $likedStatus) {
                        $likedStatus->setAggregate($firstAggregate);
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

                $aggregate = $firstAggregate;
            }
        }

        return $aggregate;
    }

    /**
     * @param array                         $aggregate
     * @param PublishersListInterface|null $matchingAggregate
     *
     * @return array
     * @throws DBALException
     * @throws ORMException
     */
    private function updateTotalMembers(
        array $aggregate,
        PublishersListInterface $matchingAggregate = null
    ): array {
        if ($aggregate['totalMembers'] === 0) {
            $query      = <<<QUERY
                SELECT 
                COUNT(a.screen_name) as total_members
                FROM weaving_aggregate a
                WHERE screen_name IS NOT NULL
                AND name in (
                    SELECT a.name
                    FROM weaving_aggregate a
                    WHERE id = ?
                )
                GROUP BY a.screen_name
QUERY;
            $connection = $this->getEntityManager()->getConnection();
            $statement  = $connection->executeQuery($query, [$aggregate['id']], [\Pdo::PARAM_INT]);

            $aggregate['totalMembers'] = (int) $statement->fetchAll([0]['total_members']);

            $matchingAggregate->totalMembers = $aggregate['totalMembers'];
            if ($aggregate['totalMembers'] === 0) {
                $matchingAggregate->totalMembers = -1;
            }

            $this->getEntityManager()->persist($matchingAggregate);
        }

        return $aggregate;
    }

    public function getAllPublishersLists(Request $request = null): array {
        $connection = $this->getEntityManager()->getConnection();

        $getMemberSubscription = <<<'QUERY'
            SELECT 
            id,
            name,
            total_members,
            total_statuses as total_status
            FROM weaving_aggregate
            WHERE screen_name IS NULL
            AND name not like ?
            ORDER BY name
QUERY;

        $statement = $connection->executeQuery(
            $getMemberSubscription,
            [self::PREFIX_MEMBER_AGGREGATE.'%'],
            [ParameterType::STRING]
        );

        return $statement->fetchAll(FetchMode::ASSOCIATIVE);
    }
}

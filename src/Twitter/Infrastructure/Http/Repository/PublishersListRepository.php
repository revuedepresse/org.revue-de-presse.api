<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListDispatcherTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Twitter\Infrastructure\Http\Resource\PublishersList as PublishersListResource;
use App\Twitter\Infrastructure\Http\SearchParams;
use App\Twitter\Infrastructure\Operation\CapableOfDeletionInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\PublishersList\Entity\TimelyStatus;
use App\Twitter\Infrastructure\PublishersList\Repository\PaginationAwareTrait;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use function array_map;
use function array_sum;

/**
 * @author revue-de-presse.org <thierrymarianne@users.noreply.github.com>
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
    use LoggerTrait;
    use PaginationAwareTrait;
    use PublishersListDispatcherTrait;
    use TimelyStatusRepositoryTrait;
    use TokenRepositoryTrait;

    public const TABLE_ALIAS = 'a';

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
        PublishersListResource $list
    ): PublishersListInterface {
        $aggregate = $this->findOneBy(
            [
                'name'       => $list->name(),
                'screenName' => $member->twitterScreenName()
            ]
        );

        if (!($aggregate instanceof PublishersList)) {
            $aggregate = $this->make($member->twitterScreenName(), $list->name());
        }

        $aggregate->listId = $list->id();

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
                    if (!($existingAggregate instanceof PublishersList)) {
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

    public function byName(
        string $screenName,
        string $listName,
        ?string $listId = null
    ): PublishersListInterface
    {
        $publishersList = $this->make(
            $screenName,
            $listName
        );

        $this->getEntityManager()->persist($publishersList);

        if ($listId !== null) {
            $publishersList->listId = $listId;
        }

        $this->getEntityManager()->flush();

        return $publishersList;
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

        return new PublishersList($screenName, $listName);
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
            FROM publishers_list
            WHERE screen_name IS NOT NULL
            AND name in (
                SELECT name 
                FROM publishers_list
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
            $records   = $statement->fetchAllAssociative();
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
                        (new Member())->setTwitterScreenName($aggregate->screenName),
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
                $aggregate->setTotalStatus(0);

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
            UPDATE publishers_list a
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
            FROM publishers_list a, weaving_user u
            WHERE screen_name IS NOT NULL 
            AND a.screen_name = u.usr_twitter_username
            AND id NOT IN (
                SELECT aggregate_id FROM weaving_status_aggregate
            );
QUERY;

        $statement = $this->getEntityManager()->getConnection()->executeQuery($selectAggregates);

        return $statement->fetchAllAssociative();
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
                      FROM publishers_list a
                      INNER JOIN publishers_list am 
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

            $aggregate['totalStatuses'] = (int) $statement->fetchAllAssociative()[0]['total_status'];

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

    private function castIds(array $aggregateIds): array
    {
        return array_map(
            fn($aggregateId) => (int) $aggregateId,
            $aggregateIds
        );
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function findByRemovingDuplicates(
        string $screenName,
        string $listName
    ) {
        $twitterList = $this->findOneBy(
            [
                'screenName' => $screenName,
                'name'       => $listName
            ]
        );

        if ($twitterList instanceof PublishersList) {
            $aggregates = $this->findBy(
                [
                    'screenName' => $screenName,
                    'name'       => $listName
                ]
            );

            if (\count($aggregates) > 1) {
                /** @var PublishersListInterface $firstAggregate */
                $firstAggregate = $aggregates[0];

                foreach ($aggregates as $index => $twitterList) {
                    if ($index === 0) {
                        continue;
                    }

                    $statuses = $this->statusRepository
                        ->findByAggregate($twitterList);

                    /** @var StatusInterface $status */
                    foreach ($statuses as $status) {
                        /** @var StatusInterface $status */
                        $status->removeFrom($twitterList);
                        $status->addToAggregates($firstAggregate);
                    }

                    $timelyStatuses = $this->timelyStatusRepository
                        ->findBy(['twitterList' => $twitterList]);

                    /** @var TimelyStatus $timelyStatus */
                    foreach ($timelyStatuses as $timelyStatus) {
                        $timelyStatus->tagAsBelongingToTwitterList($firstAggregate);
                    }
                }

                $this->getEntityManager()->flush();

                foreach ($aggregates as $index => $twitterList) {
                    if ($index === 0) {
                        continue;
                    }

                    $this->getEntityManager()->remove($twitterList);
                }

                $this->getEntityManager()->flush();

                $twitterList = $firstAggregate;
            }
        }

        return $twitterList;
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
                FROM publishers_list a
                WHERE screen_name IS NOT NULL
                AND name in (
                    SELECT a.name
                    FROM publishers_list a
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

    public function allPublishersLists(Request $request = null): array {
        $connection = $this->getEntityManager()->getConnection();

        $getMemberSubscription = <<<QUERY
            SELECT 
            id,
            name,
            total_members,
            total_statuses as total_status
            FROM publishers_list
            WHERE screen_name IS NULL
            AND name not like ?
            ORDER BY name
QUERY;

        $statement = $connection->executeQuery(
            $getMemberSubscription,
            [self::PREFIX_MEMBER_AGGREGATE.'%'],
            [ParameterType::STRING]
        );

        return $statement->fetchAllAssociative();
    }

    public function publicPublishersList(): array
    {
        try {
            $connection = $this->getEntityManager()->getConnection();

            $findPublicPublishersList = <<<QUERY
                SELECT DISTINCT name as name, list_id as id
                FROM 
                publishers_list l,
                publishers_list_route r
                WHERE r.public_id = l.public_id
                GROUP BY name, list_id
    QUERY;

            $statement = $connection->executeQuery($findPublicPublishersList);

            return $statement->fetchAllAssociative();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return [];
        }
    }
}

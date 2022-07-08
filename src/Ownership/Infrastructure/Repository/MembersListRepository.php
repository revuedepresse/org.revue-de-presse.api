<?php
declare(strict_types=1);

namespace App\Ownership\Infrastructure\Repository;

use App\Membership\Domain\Entity\MemberInterface;
use App\Ownership\Domain\Entity\MembersList;
use App\Trends\Domain\Entity\TimelyStatus;
use App\Trends\Infrastructure\Repository\PaginationAwareTrait;
use App\Twitter\Domain\Publication\MembersListInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Domain\PublishersList\Repository\MembersListRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Twitter\Infrastructure\Http\Repository\ResourceRepository;
use App\Twitter\Infrastructure\Http\SearchParams;
use App\Twitter\Infrastructure\Operation\CapableOfDeletionInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;
use Exception;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use function array_map;

/**
 * @author revue-de-presse.org <thierrymarianne@users.noreply.github.com>
 *
 * @method MembersListInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method MembersListInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method MembersListInterface[]    findAll()
 * @method MembersListInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembersListRepository extends ResourceRepository implements CapableOfDeletionInterface,
    MembersListRepositoryInterface
{
    use StatusRepositoryTrait;
    use LoggerTrait;
    use PaginationAwareTrait;
    use TimelyStatusRepositoryTrait;
    use TokenRepositoryTrait;

    private const TABLE_ALIAS = 'a';

    private const PREFIX_MEMBER_AGGREGATE = 'user :: ';

    public function addMemberToList(
        MemberInterface $member,
        stdClass $list
    ): MembersListInterface {
        $list = $this->findOneBy(
            [
                'name'       => $list->name,
                'screenName' => $member->getTwitterUsername()
            ]
        );

        if (!($list instanceof MembersList)) {
            $list = $this->make($member->getTwitterUsername(), $list->getName());
        }

        $list->listId = $list->listId();

        return $this->save($list);
    }

    /**
     * @param SearchParams $searchParams
     *
     * @return int
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
            function (array $list) {
                $existingAggregate = null;
                if ($list['totalStatuses'] === 0) {
                    /** @var MembersListInterface $existingAggregate */
                    $existingAggregate = $this->findOneBy(['id' => $list['id']]);
                    if (!($existingAggregate instanceof MembersList)) {
                        return $list;
                    }
                }

                $list = $this->updateTotalStatuses(
                    $list,
                    $existingAggregate
                );

                return $this->updateTotalMembers($list, $existingAggregate);
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

    public function getListAggregateByName(
        string $screenName,
        string $listName,
        string $listId = null
    ) {
        $list = $this->make(
            $screenName,
            $listName
        );

        $this->getEntityManager()->persist($list);

        if ($listId !== null) {
            $list->listId = $listId;
        }

        $this->getEntityManager()->flush();

        return $list;
    }

    public function getMemberAggregateByUsername(string $username): MembersListInterface
    {
        $list = $this->make(
            $username,
            self::PREFIX_MEMBER_AGGREGATE . $username
        );

        $this->getEntityManager()->persist($list);
        $this->getEntityManager()->flush();

        return $list;
    }

    public function lockAggregate(MembersListInterface $list)
    {
        $list->lock();

        $this->save($list);
    }

    public function make(string $screenName, string $listName): MembersListInterface
    {
        $list = $this->findByRemovingDuplicates(
            $screenName,
            $listName
        );

        if ($list instanceof MembersListInterface) {
            return $list;
        }

        return new MembersList($screenName, $listName);
    }

    /**
     * @param MembersListInterface $list
     *
     * @return MembersListInterface
     */
    public function save(MembersListInterface $list)
    {
        $this->getEntityManager()->persist($list);
        $this->getEntityManager()->flush();

        return $list;
    }

    public function unlockPublishersList(MembersListInterface $list): MembersListInterface
    {
        $list->unlock();

        return $this->save($list);
    }

    public function updateTotalStatuses(
        array                 $list,
        ?MembersListInterface $matchingAggregate = null,
        bool                  $includeRelatedAggregates = true
    ): array {
        if ($list['totalStatuses'] <= 0) {
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
                [$list['id']],
                [\PDO::PARAM_INT]
            );

            $list['totalStatuses'] = (int) $statement->fetchAll()[0]['total_status'];

            $matchingAggregate->setTotalStatus($list['totalStatuses']);
            if ($list['totalStatuses'] === 0) {
                $matchingAggregate->setTotalStatus(-1);
            }

            $this->getEntityManager()->persist($matchingAggregate);
        }

        return $list;
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
     * @throws Exception
     */
    private function findByRemovingDuplicates(
        string $screenName,
        string $listName
    ) {
        $list = $this->findOneBy(
            [
                'screenName' => $screenName,
                'name'       => $listName
            ]
        );

        if ($list instanceof MembersList) {
            $aggregates = $this->findBy(
                [
                    'screenName' => $screenName,
                    'name'       => $listName
                ]
            );

            if (\count($aggregates) > 1) {
                /** @var MembersListInterface $firstAggregate */
                $firstAggregate = $aggregates[0];

                foreach ($aggregates as $index => $list) {
                    if ($index === 0) {
                        continue;
                    }

                    $statuses = $this->statusRepository
                        ->findByAggregate($list);

                    /** @var StatusInterface $status */
                    foreach ($statuses as $status) {
                        /** @var StatusInterface $status */
                        $status->removeFrom($list);
                        $status->addToMembersList($firstAggregate);
                    }

                    $timelyStatuses = $this->timelyStatusRepository
                        ->findBy(['aggregate' => $list]);

                    /** @var TimelyStatus $timelyStatus */
                    foreach ($timelyStatuses as $timelyStatus) {
                        $timelyStatus->updateAggregate($firstAggregate);
                    }
                }

                $this->getEntityManager()->flush();

                foreach ($aggregates as $index => $list) {
                    if ($index === 0) {
                        continue;
                    }

                    $this->getEntityManager()->remove($list);
                }

                $this->getEntityManager()->flush();

                $list = $firstAggregate;
            }
        }

        return $list;
    }

    private function updateTotalMembers(
        array $list,
        MembersListInterface $matchingAggregate = null
    ): array {
        if ($list['totalMembers'] === 0) {
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
            $statement  = $connection->executeQuery($query, [$list['id']], [\Pdo::PARAM_INT]);

            $list['totalMembers'] = (int) $statement->fetchAll([0]['total_members']);

            $matchingAggregate->totalMembers = $list['totalMembers'];
            if ($list['totalMembers'] === 0) {
                $matchingAggregate->totalMembers = -1;
            }

            $this->getEntityManager()->persist($matchingAggregate);
        }

        return $list;
    }

    public function getAllPublishersLists(Request $request = null): array {
        $connection = $this->getEntityManager()->getConnection();

        $getMemberSubscription = <<<'QUERY'
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
}

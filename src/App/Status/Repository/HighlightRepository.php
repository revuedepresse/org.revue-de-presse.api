<?php

namespace App\Status\Repository;

use App\Aggregate\Controller\SearchParams;
use App\Aggregate\Repository\PaginationAwareTrait;
use App\Conversation\ConversationAwareTrait;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

class HighlightRepository extends EntityRepository implements PaginationAwareRepositoryInterface
{
    use PaginationAwareTrait;
    use ConversationAwareTrait;

    const TABLE_ALIAS = 'h';

    public function __construct($entityManager, ClassMetadata $class)
    {
        parent::__construct($entityManager, $class);
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
    public function findHighlights(SearchParams $searchParams): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);

        $queryBuilder->select('s.statusId as status_id');
        $queryBuilder->addSelect('s.apiDocument as original_document');
        $queryBuilder->addSelect('s.text');
        $queryBuilder->addSelect('s.createdAt as publicationDateTime');
        $queryBuilder->addSelect('s.screenName as screen_name');
        $queryBuilder->addSelect(self::TABLE_ALIAS.'.totalRetweets');

        $queryBuilder->join(self::TABLE_ALIAS.'.status', 's');
        $queryBuilder->join(self::TABLE_ALIAS.'.member', 'm');

        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());
        $queryBuilder->setMaxResults(min($searchParams->getPageSize(), 10));
        $queryBuilder->orderBy(self::TABLE_ALIAS.'.totalRetweets', 'DESC');

        $this->applyCriteria($queryBuilder, $searchParams);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $statuses = array_map(
            function ($status) {
                $extractedProperties = [
                    'status' => $this->extractStatusProperties(
                        [$status],
                        false)[0]
                ];

                unset($status['original_document']);
                unset($status['screen_name']);
                unset($status['author_avatar']);
                unset($status['status_id']);

                return array_merge($status, $extractedProperties);
            },
            $results
        );

        return $statuses;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     */
    public function applyCriteria(QueryBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $queryBuilder->andWhere("DATE(DATEADD(".self::TABLE_ALIAS.".publicationDateTime, 1, 'HOUR')) = :date");
        $queryBuilder->setParameter('date', $searchParams->getParams()['date']);
    }
}

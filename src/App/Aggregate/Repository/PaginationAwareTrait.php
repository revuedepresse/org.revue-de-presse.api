<?php

namespace App\Aggregate\Repository;

use App\Http\SearchParams;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

trait PaginationAwareTrait
{
    /**
     * @param SearchParams $searchParams
     * @param              $alias
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function howManyPages(SearchParams $searchParams, $alias): int
    {
        /**
         * @var QueryBuilder $queryBuilder
         */
        $queryBuilder = $this->createQueryBuilder($alias);

        $this->applyCriteria($queryBuilder, $searchParams);

        $uniqueIdentifier = $alias.'.id';

        if ($this instanceof DuplicateRecordsAware) {
            $uniqueIdentifier = $this->getUniqueIdentifier();
        }

        $queryBuilder->select(
            sprintf(
                'COUNT(DISTINCT %s) total_items',
                $uniqueIdentifier
            )
        );

        try {
            $result = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            return 0;
        }

        return ceil(intval($result['total_items']) / $searchParams->getPageSize());
    }
}

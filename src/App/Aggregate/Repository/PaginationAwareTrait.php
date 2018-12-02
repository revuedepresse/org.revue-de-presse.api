<?php

namespace App\Aggregate\Repository;

use App\Aggregate\Controller\SearchParams;
use Doctrine\ORM\NoResultException;

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
        $queryBuilder = $this->createQueryBuilder($alias);

        $this->applyCriteria($queryBuilder, $searchParams);
        $queryBuilder->select('count('.$alias.'.id) total_items');

        try {
            $result = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            return 0;
        }

        return ceil($result['total_items'] / $searchParams->getPageSize());
    }
}

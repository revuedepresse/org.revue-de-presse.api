<?php

namespace App\Status\Repository;

use App\Http\SearchParams;
use Doctrine\ORM\QueryBuilder;

interface PaginationAwareRepositoryInterface
{
    /**
     * @param SearchParams $searchParams
     * @return int
     */
    public function countTotalPages(SearchParams $searchParams): int;


    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     */
    public function applyCriteria(QueryBuilder $queryBuilder, SearchParams $searchParams): void;
}

<?php

namespace App\Twitter\Infrastructure\Operation;

use Doctrine\ORM\QueryBuilder;

interface CapableOfDeletionInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     * @return mixed
     */
    public function excludeDeletedRecords(QueryBuilder $queryBuilder);
}

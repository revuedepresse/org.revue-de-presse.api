<?php

namespace App\Operation;

use Doctrine\ORM\QueryBuilder;

interface CapableOfDeletionInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     * @return mixed
     */
    public function excludeDeletedRecords(QueryBuilder $queryBuilder);
}

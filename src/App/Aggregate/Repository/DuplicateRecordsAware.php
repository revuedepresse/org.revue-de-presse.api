<?php

namespace App\Aggregate\Repository;

interface DuplicateRecordsAware
{
    public function getUniqueIdentifier();
}

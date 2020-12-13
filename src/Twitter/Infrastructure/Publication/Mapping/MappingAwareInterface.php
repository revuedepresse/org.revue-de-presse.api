<?php

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Infrastructure\Api\Entity\Status;

interface MappingAwareInterface
{
    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status;
}

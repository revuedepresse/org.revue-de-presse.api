<?php

namespace App\Infrastructure\Publication\Mapping;

use App\Infrastructure\Api\Entity\Status;

interface MappingAwareInterface
{
    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status;
}

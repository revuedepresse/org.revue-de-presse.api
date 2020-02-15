<?php

namespace App\StatusCollection\Mapping;

use App\Api\Entity\Status;

interface MappingAwareInterface
{
    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status;
}

<?php

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Infrastructure\Http\Entity\Status;

interface MappingAwareInterface
{
    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status;
}

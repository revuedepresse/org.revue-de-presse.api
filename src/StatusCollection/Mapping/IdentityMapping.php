<?php

namespace App\StatusCollection\Mapping;

use App\Api\Entity\Status;

class IdentityMapping implements MappingAwareInterface
{
    public function apply(Status $status): Status {
        return $status;
    }
}

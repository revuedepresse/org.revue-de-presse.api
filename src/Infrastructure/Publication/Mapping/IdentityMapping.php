<?php

namespace App\Infrastructure\Publication\Mapping;

use App\Infrastructure\Api\Entity\Status;

class IdentityMapping implements MappingAwareInterface
{
    public function apply(Status $status): Status {
        return $status;
    }
}

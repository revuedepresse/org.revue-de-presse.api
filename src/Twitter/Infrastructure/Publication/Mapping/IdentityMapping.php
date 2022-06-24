<?php

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Infrastructure\Http\Entity\Status;

class IdentityMapping implements MappingAwareInterface
{
    public function apply(Status $status): Status {
        return $status;
    }
}

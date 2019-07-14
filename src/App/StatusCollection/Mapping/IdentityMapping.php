<?php

namespace App\StatusCollection\Mapping;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;

class IdentityMapping implements MappingAwareInterface
{
    public function apply(Status $status): Status {
        return $status;
    }
}

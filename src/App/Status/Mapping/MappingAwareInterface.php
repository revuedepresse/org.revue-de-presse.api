<?php

namespace App\Status\Mapping;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;

interface MappingAwareInterface
{
    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status;
}

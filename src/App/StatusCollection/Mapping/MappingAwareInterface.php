<?php

namespace App\StatusCollection\Mapping;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;

interface MappingAwareInterface
{
    /**
     * @param Status $status
     * @return Status
     */
    public function apply(Status $status): Status;
}

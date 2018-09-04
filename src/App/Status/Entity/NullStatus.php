<?php

namespace App\Status\Entity;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

class NullStatus extends Status implements StatusInterface
{
    /**
     * @return int
     */
    public function getId()
    {
        return -1;
    }
}

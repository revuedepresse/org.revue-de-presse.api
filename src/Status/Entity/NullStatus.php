<?php

namespace App\Status\Entity;

use App\Api\Entity\Status;
use App\Api\Entity\StatusInterface;

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

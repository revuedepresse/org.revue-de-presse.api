<?php

namespace App\Status\Entity;

use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;

class NotFoundStatus
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Status
     */
    private $status = null;

    /**
     * @var ArchivedStatus
     */
    private $archivedStatus = null;
}

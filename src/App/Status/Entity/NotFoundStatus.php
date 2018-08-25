<?php

namespace App\Status\Entity;

use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

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

    /**
     * @param $status
     * @param $archivedStatus
     */
    public function __construct(Status $status = null, ArchivedStatus $archivedStatus = null)
    {
        $this->archivedStatus = $archivedStatus;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return StatusInterface
     */
    public function getStatus()
    {
        if (is_null($this->status)) {
            return $this->archivedStatus;
        }

        return $this->status;
    }
}

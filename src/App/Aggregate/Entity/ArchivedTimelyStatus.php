<?php

namespace App\Aggregate\Entity;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;

class ArchivedTimelyStatus
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var ArchivedStatus
     */
    private $status;

    /**
     * @var Aggregate
     */
    private $aggregate;

    /**
     * @var \DateTime
     */
    private $publicationDateTime;

    /**
     * @var string
     */
    private $aggregateName;

    /**
     * @var int
     */
    private $timeRange;

    /**
     * @var string
     */
    private $memberName;
}

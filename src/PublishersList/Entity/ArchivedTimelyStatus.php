<?php

namespace App\PublishersList\Entity;

use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;

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

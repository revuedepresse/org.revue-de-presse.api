<?php

namespace App\Twitter\Infrastructure\PublishersList\Entity;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;
use DateTime;

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

    private PublishersList $aggregate;

    /**
     * @var DateTime
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

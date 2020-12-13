<?php

namespace App\PublishersList\Entity;

use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\StatusInterface;

class SearchMatchingStatus implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $memberName;

    /**
     * @var \DateTime
     */
    private $publicationDateTime;

    /**
     * @var Status
     */
    public $status;

    /**
     * @var SavedSearch
     */
    private $savedSearch;

    /**
     * @var int
     */
    private $timeRange;

    /**
     * @param StatusInterface $status
     */
    public function __construct(StatusInterface $status, SavedSearch $savedSearch)
    {
        $this->status = $status;
        $this->savedSearch = $savedSearch;
        $this->publicationDateTime = $status->getCreatedAt();
        $this->memberName  = $status->getScreenName();

        $this->updateTimeRange();
    }
}

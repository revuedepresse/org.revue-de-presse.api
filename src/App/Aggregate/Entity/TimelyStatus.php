<?php

namespace App\Aggregate\Entity;

use App\TimeRange\TimeRangeAwareTrait;
use App\TimeRange\TimeRangeAwareInterface;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

class TimelyStatus implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var Status
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

    public function __construct(
        StatusInterface $status,
        Aggregate $aggregate,
        \DateTime $publicationDateTime
    ) {

        $this->status = $status;
        $this->aggregate = $aggregate;
        $this->publicationDateTime = $publicationDateTime;

        $this->aggregateName = $this->aggregate->getName();
        $this->memberName = $status->getScreenName();

        $this->updateTimeRange();
    }
}

<?php

namespace App\Aggregate\Entity;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

class TimelyStatus
{
    const RANGE_SINCE_5_MIN_AGO = 0;

    const RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO = 1;

    const RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO = 2;

    const RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO = 3;

    const RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO = 4;

    const RANGE_OVER_1_WEEK_AGO = 5;

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

    /**
     * @return TimelyStatus
     * @throws \Exception
     */
    public function updateTimeRange(): TimelyStatus
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $fiveMinutesAgo = (clone $now)->sub(new \DateInterval('PT5M'));
        $tenMinutesAgo = (clone $now)->sub(new \DateInterval('PT10M'));
        $thirtyMinutesAgo = (clone $now)->sub(new \DateInterval('PT30M'));
        $oneDayAgo = (clone $now)->sub(new \DateInterval('P1D'));
        $oneWeekAgo = (clone $now)->sub(new \DateInterval('P1W'));

        $status = $this->status;

        if ($status->getCreatedAt() > $fiveMinutesAgo) {
            $this->timeRange = self::RANGE_SINCE_5_MIN_AGO;
            return $this;
        }

        if ($status->getCreatedAt() > $fiveMinutesAgo && $status->getCreatedAt() > $tenMinutesAgo) {
            $this->timeRange = self::RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO;
            return $this;
        }

        if ($status->getCreatedAt() < $tenMinutesAgo && $status->getCreatedAt() > $thirtyMinutesAgo) {
            $this->timeRange = self::RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO;
            return $this;
        }

        if ($status->getCreatedAt() < $thirtyMinutesAgo && $status->getCreatedAt() > $oneDayAgo) {
            $this->timeRange = self::RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO;
            return $this;
        }

        if ($status->getCreatedAt() < $oneDayAgo && $status->getCreatedAt() > $oneWeekAgo) {
            $this->timeRange = self::RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO;
            return $this;
        }

        $this->timeRange = self::RANGE_OVER_1_WEEK_AGO;

        return $this;
    }
}

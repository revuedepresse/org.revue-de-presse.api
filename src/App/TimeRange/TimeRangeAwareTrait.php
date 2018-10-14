<?php


namespace App\TimeRange;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;

/**
 * @package App\Status
 */
trait TimeRangeAwareTrait
{
    /**
     * @return TimeRangeAwareInterface
     * @throws \Exception
     */
    public function updateTimeRange(): TimeRangeAwareInterface
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $fiveMinutesAgo = (clone $now)->sub(new \DateInterval('PT5M'));
        $tenMinutesAgo = (clone $now)->sub(new \DateInterval('PT10M'));
        $thirtyMinutesAgo = (clone $now)->sub(new \DateInterval('PT30M'));
        $oneDayAgo = (clone $now)->sub(new \DateInterval('P1D'));
        $oneWeekAgo = (clone $now)->sub(new \DateInterval('P1W'));

        /** @var Status $status */
        $status = $this->status;

        if ($status->getCreatedAt() > $fiveMinutesAgo) {
            $this->timeRange = self::RANGE_SINCE_5_MIN_AGO;

            /** @var TimeRangeAwareInterface $this */
            return $this;
        }

        if ($status->getCreatedAt() > $fiveMinutesAgo && $status->getCreatedAt() > $tenMinutesAgo) {
            $this->timeRange = self::RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO;

            /** @var TimeRangeAwareInterface $this */
            return $this;
        }

        if ($status->getCreatedAt() < $tenMinutesAgo && $status->getCreatedAt() > $thirtyMinutesAgo) {
            $this->timeRange = self::RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO;

            /** @var TimeRangeAwareInterface $this */
            return $this;
        }

        if ($status->getCreatedAt() < $thirtyMinutesAgo && $status->getCreatedAt() > $oneDayAgo) {
            $this->timeRange = self::RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO;

            /** @var TimeRangeAwareInterface $this */
            return $this;
        }

        if ($status->getCreatedAt() < $oneDayAgo && $status->getCreatedAt() > $oneWeekAgo) {
            $this->timeRange = self::RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO;

            /** @var TimeRangeAwareInterface $this */
            return $this;
        }

        $this->timeRange = self::RANGE_OVER_1_WEEK_AGO;

        /** @var TimeRangeAwareInterface $this */
        return $this;
    }
}

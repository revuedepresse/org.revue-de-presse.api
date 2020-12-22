<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Clock\TimeRange;

use App\Twitter\Infrastructure\Api\Entity\Status;
use DateTime;
use Exception;

trait TimeRangeAwareTrait
{
    /**
     * @return TimeRangeAwareInterface
     * @throws Exception
     */
    public function updateTimeRange(): TimeRangeAwareInterface
    {
        /** @var Status $status */
        $status = $this->status;
        $statusPublicationDate = $status->getCreatedAt();

        $this->timeRange = $this->mapDateToTimeRange($statusPublicationDate);

        /** @var TimeRangeAwareInterface $this */
        return $this;
    }

    /**
     * @param DateTime $statusPublicationDate
     * @return int
     * @throws Exception
     */
    public function mapDateToTimeRange(DateTime $statusPublicationDate)
    {
        $now = new DateTime('now', new \DateTimeZone('UTC'));

        $fiveMinutesAgo = (clone $now)->sub(new \DateInterval('PT5M'));
        $tenMinutesAgo = (clone $now)->sub(new \DateInterval('PT10M'));
        $thirtyMinutesAgo = (clone $now)->sub(new \DateInterval('PT30M'));
        $oneDayAgo = (clone $now)->sub(new \DateInterval('P1D'));
        $oneWeekAgo = (clone $now)->sub(new \DateInterval('P1W'));

        if ($statusPublicationDate > $fiveMinutesAgo) {
            $timeRange = self::RANGE_SINCE_5_MIN_AGO;

            return $timeRange;
        }

        if ($statusPublicationDate > $fiveMinutesAgo && $statusPublicationDate > $tenMinutesAgo) {
            $timeRange = self::RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO;

            return $timeRange;
        }

        if ($statusPublicationDate < $tenMinutesAgo && $statusPublicationDate > $thirtyMinutesAgo) {
            $timeRange = self::RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO;

            return $timeRange;
        }

        if ($statusPublicationDate < $thirtyMinutesAgo && $statusPublicationDate > $oneDayAgo) {
            $timeRange = self::RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO;

            return $timeRange;
        }

        if ($statusPublicationDate < $oneDayAgo && $statusPublicationDate > $oneWeekAgo) {
            $timeRange = self::RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO;

            return $timeRange;
        }

        $timeRange = self::RANGE_OVER_1_WEEK_AGO;

        return $timeRange;
    }
}

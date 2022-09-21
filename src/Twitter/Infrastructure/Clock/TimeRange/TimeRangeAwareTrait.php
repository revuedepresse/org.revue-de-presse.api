<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Clock\TimeRange;

use App\Twitter\Infrastructure\Http\Entity\Tweet;
use DateTime;
use Exception;

trait TimeRangeAwareTrait
{
    public function updateTimeRange(): TimeRangeAwareInterface
    {
        /** @var Tweet $tweet */
        $tweet = $this->tweet;
        $tweetPublicationDate = $tweet->getCreatedAt();

        $this->timeRange = $this->mapDateToTimeRange($tweetPublicationDate);

        return $this;
    }

    public function mapDateToTimeRange(\DateTimeInterface $tweetPublicationDate)
    {
        $now = new DateTime('now', new \DateTimeZone('UTC'));

        $fiveMinutesAgo = (clone $now)->sub(new \DateInterval('PT5M'));
        $tenMinutesAgo = (clone $now)->sub(new \DateInterval('PT10M'));
        $thirtyMinutesAgo = (clone $now)->sub(new \DateInterval('PT30M'));
        $oneDayAgo = (clone $now)->sub(new \DateInterval('P1D'));
        $oneWeekAgo = (clone $now)->sub(new \DateInterval('P1W'));

        if ($tweetPublicationDate > $fiveMinutesAgo) {
            $timeRange = self::RANGE_SINCE_5_MIN_AGO;

            return $timeRange;
        }

        if ($tweetPublicationDate > $fiveMinutesAgo && $tweetPublicationDate > $tenMinutesAgo) {
            $timeRange = self::RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO;

            return $timeRange;
        }

        if ($tweetPublicationDate < $tenMinutesAgo && $tweetPublicationDate > $thirtyMinutesAgo) {
            $timeRange = self::RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO;

            return $timeRange;
        }

        if ($tweetPublicationDate < $thirtyMinutesAgo && $tweetPublicationDate > $oneDayAgo) {
            $timeRange = self::RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO;

            return $timeRange;
        }

        if ($tweetPublicationDate < $oneDayAgo && $tweetPublicationDate > $oneWeekAgo) {
            $timeRange = self::RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO;

            return $timeRange;
        }

        $timeRange = self::RANGE_OVER_1_WEEK_AGO;

        return $timeRange;
    }
}

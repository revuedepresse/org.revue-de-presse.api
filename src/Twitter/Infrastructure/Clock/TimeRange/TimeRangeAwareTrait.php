<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Clock\TimeRange;

use App\Twitter\Infrastructure\Http\Entity\Tweet;

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

    public function mapDateToTimeRange(\DateTimeInterface $publicationDate): int
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $fiveMinutesAgo = (clone $now)->sub(new \DateInterval('PT5M'));
        $tenMinutesAgo = (clone $now)->sub(new \DateInterval('PT10M'));
        $thirtyMinutesAgo = (clone $now)->sub(new \DateInterval('PT30M'));
        $oneDayAgo = (clone $now)->sub(new \DateInterval('P1D'));
        $oneWeekAgo = (clone $now)->sub(new \DateInterval('P1W'));

        if ($publicationDate > $fiveMinutesAgo && $publicationDate <= $now) {
            return self::RANGE_SINCE_5_MIN_AGO;
        }

        if ($publicationDate <= $fiveMinutesAgo && $publicationDate > $tenMinutesAgo) {
            return self::RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO;
        }

        if ($publicationDate <= $tenMinutesAgo && $publicationDate > $thirtyMinutesAgo) {
            return self::RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO;
        }

        if ($publicationDate <= $thirtyMinutesAgo && $publicationDate > $oneDayAgo) {
            return self::RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO;
        }

        if ($publicationDate <= $oneDayAgo && $publicationDate > $oneWeekAgo) {
            return self::RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO;
        }

        return self::RANGE_OVER_1_WEEK_AGO;
    }
}

<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Clock\TimeRange;

interface TimeRangeAwareInterface
{
    public const RANGE_SINCE_5_MIN_AGO = 0;

    public const RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO = 1;

    public const RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO = 2;

    public const RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO = 3;

    public const RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO = 4;

    public const RANGE_OVER_1_WEEK_AGO = 5;

    public function updateTimeRange(): TimeRangeAwareInterface;
}

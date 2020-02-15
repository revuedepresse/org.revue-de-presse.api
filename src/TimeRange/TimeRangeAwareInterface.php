<?php

namespace App\TimeRange;

interface TimeRangeAwareInterface
{
    const RANGE_SINCE_5_MIN_AGO = 0;

    const RANGE_FROM_10_MIN_AGO_TO_5_MIN_AGO = 1;

    const RANGE_FROM_30_MIN_AGO_TO_10_MIN_AGO = 2;

    const RANGE_FROM_1_DAY_AGO_TO_30_MIN_AGO = 3;

    const RANGE_FROM_1_WEEK_AGO_TO_1_DAY_AGO = 4;

    const RANGE_OVER_1_WEEK_AGO = 5;

    public function updateTimeRange(): self;
}

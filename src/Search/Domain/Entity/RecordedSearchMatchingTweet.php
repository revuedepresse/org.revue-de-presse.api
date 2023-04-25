<?php

namespace App\Search\Domain\Entity;

use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use Ramsey\Uuid\UuidInterface;

class RecordedSearchMatchingTweet extends SearchMatchingTweet
{
    public static function fromSearchQueryMatchingTweet(SearchMatchingTweet $tweet): self {
        return new self($tweet->tweet, $tweet->savedSearch);
    }
}

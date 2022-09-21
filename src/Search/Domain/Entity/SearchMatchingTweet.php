<?php

namespace App\Search\Domain\Entity;

use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use Ramsey\Uuid\UuidInterface;

class SearchMatchingTweet implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    private UuidInterface $id;

    private string $memberName;

    private \DateTimeInterface $publicationDateTime;

    public readonly TweetInterface $tweet;

    public readonly SavedSearch $savedSearch;

    private int $timeRange;

    public function __construct(TweetInterface $tweet, SavedSearch $savedSearch)
    {
        $this->memberName  = $tweet->getScreenName();
        $this->publicationDateTime = $tweet->getCreatedAt();
        $this->savedSearch = $savedSearch;
        $this->tweet = $tweet;

        $this->updateTimeRange();
    }
}

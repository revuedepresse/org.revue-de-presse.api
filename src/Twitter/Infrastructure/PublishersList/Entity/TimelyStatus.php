<?php

namespace App\Twitter\Infrastructure\PublishersList\Entity;

use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\TweetInterface;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class TimelyStatus implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    private UuidInterface     $id;
    private string            $memberName;
    private DateTimeInterface $publicationDateTime;
    private TweetInterface    $tweet;
    private PublishersList    $twitterList;
    private int               $timeRange;
    private string            $twitterListName;

    public function __construct(
        TweetInterface     $tweet,
        PublishersList     $twitterList,
        \DateTimeInterface $publicationDateTime
    ) {
        $this->memberName = $tweet->getScreenName();
        $this->publicationDateTime = $publicationDateTime;
        $this->tweet = $tweet;
        $this->twitterList = $twitterList;
        $this->twitterListName = $this->twitterList->name();

        $this->updateTimeRange();
    }

    public function tagAsBelongingToTwitterList(PublishersList $twitterList)
    {
        $this->twitterList = $twitterList;
        $this->twitterListName = $twitterList->name();

        $this->updateTimeRange();
    }
}

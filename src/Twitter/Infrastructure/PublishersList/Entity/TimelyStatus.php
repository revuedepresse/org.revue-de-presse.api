<?php

namespace App\Twitter\Infrastructure\PublishersList\Entity;

use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class TimelyStatus implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    private UuidInterface     $id;
    private string            $memberName;
    private DateTimeInterface $publicationDateTime;
    private StatusInterface   $status;
    private PublishersList    $twitterList;
    private int               $timeRange;
    private string            $twitterListName;

    public function __construct(
        StatusInterface    $status,
        PublishersList     $twitterList,
        \DateTimeInterface $publicationDateTime
    ) {
        $this->memberName = $status->getScreenName();
        $this->publicationDateTime = $publicationDateTime;
        $this->status = $status;
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

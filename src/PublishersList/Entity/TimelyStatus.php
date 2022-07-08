<?php

namespace App\PublishersList\Entity;

use App\Twitter\Domain\Publication\MembersListInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Membership\Domain\Ownership\MembersList;
use App\Twitter\Infrastructure\Http\Entity\Status;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class TimelyStatus implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    private UuidInterface $id;

    private StatusInterface $status;

    private MembersListInterface $list;

    private DateTimeInterface $publicationDateTime;

    private string $aggregateName;

    private int $timeRange;

    private string $memberName;

    public function __construct(
        StatusInterface    $status,
        MembersList        $list,
        \DateTimeInterface $publicationDateTime
    ) {

        $this->status = $status;
        $this->list = $list;
        $this->publicationDateTime = $publicationDateTime;

        $this->aggregateName = $this->list->getName();
        $this->memberName = $status->getScreenName();

        $this->updateTimeRange();
    }

    /**
     * @throws \Exception
     */
    public function updateAggregate(MembersListInterface $list)
    {
        $this->list = $list;
        $this->aggregateName = $list->getName();

        $this->updateTimeRange();
    }
}

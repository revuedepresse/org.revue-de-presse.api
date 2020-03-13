<?php

namespace App\PublishersList\Entity;

use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\Status;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class TimelyStatus implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    private UuidInterface $id;

    private StatusInterface $status;

    private PublishersListInterface $aggregate;

    private DateTimeInterface $publicationDateTime;

    private string $aggregateName;

    private int $timeRange;

    private string $memberName;

    public function __construct(
        StatusInterface    $status,
        PublishersList     $aggregate,
        \DateTimeInterface $publicationDateTime
    ) {

        $this->status = $status;
        $this->aggregate = $aggregate;
        $this->publicationDateTime = $publicationDateTime;

        $this->aggregateName = $this->aggregate->getName();
        $this->memberName = $status->getScreenName();

        $this->updateTimeRange();
    }

    /**
     * @throws \Exception
     */
    public function updateAggregate(PublishersListInterface $aggregate)
    {
        $this->aggregate = $aggregate;
        $this->aggregateName = $aggregate->getName();

        $this->updateTimeRange();
    }
}

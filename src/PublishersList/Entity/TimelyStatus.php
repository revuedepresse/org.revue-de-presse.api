<?php

namespace App\PublishersList\Entity;

use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareTrait;
use App\Twitter\Infrastructure\Clock\TimeRange\TimeRangeAwareInterface;
use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class TimelyStatus implements TimeRangeAwareInterface
{
    use TimeRangeAwareTrait;

    /**
     * @var string
     */
    private UuidInterface $id;

    /**
     * @var Status
     */
    private StatusInterface $status;

    /**
     * @var Aggregate
     */
    private Aggregate $aggregate;

    /**
     * @var DateTimeInterface
     */
    private DateTimeInterface $publicationDateTime;

    /**
     * @var string
     */
    private string $aggregateName;

    /**
     * @var int
     */
    private int $timeRange;

    /**
     * @var string
     */
    private string $memberName;

    public function __construct(
        StatusInterface $status,
        Aggregate $aggregate,
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
     * @param Aggregate $aggregate
     * @throws \Exception
     */
    public function updateAggregate(Aggregate $aggregate)
    {
        $this->aggregate = $aggregate;
        $this->aggregateName = $aggregate->getName();

        $this->updateTimeRange();
    }
}

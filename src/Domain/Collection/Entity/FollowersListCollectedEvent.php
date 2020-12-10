<?php
declare(strict_types=1);

namespace App\Domain\Collection\Entity;

use App\Infrastructure\Twitter\Api\Selector\ListSelector;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class FollowersListCollectedEvent implements ListCollectedEvent
{
    private UuidInterface $id;

    private UuidInterface $correlationId;

    private ?string $payload;

    private DateTimeInterface $occurredAt;

    private string $screenName;

    private string $atCursor;

    private DateTimeInterface $startedAt;

    private ?DateTimeInterface $endedAt;

    public function __construct(
        ListSelector $selector,
        DateTimeInterface $occurredAt,
        DateTimeInterface $startedAt,
        ?string $payload = null,
        ?DateTimeInterface $endedAt = null
    ) {
        $this->correlationId = $selector->correlationId();
        $this->screenName    = $selector->screenName();
        $this->atCursor      = $selector->cursor();
        $this->payload       = $payload;
        $this->occurredAt    = $occurredAt;
        $this->startedAt     = $startedAt;
        $this->endedAt       = $endedAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function correlationId(): UuidInterface
    {
        return $this->correlationId;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }

    public function atCursor(): string
    {
        return $this->atCursor;
    }

    public function occurredAt(): DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function startedAt(): DateTimeInterface
    {
        return $this->startedAt;
    }

    public function endedAt(): DateTimeInterface
    {
        return $this->endedAt;
    }

    public function finishCollect(string $payload): self
    {
        $this->payload = $payload;
        $this->endedAt = new \DateTimeImmutable();

        return $this;
    }
}
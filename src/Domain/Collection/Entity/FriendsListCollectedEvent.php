<?php
declare(strict_types=1);

namespace App\Domain\Collection\Entity;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class FriendsListCollectedEvent
{
    private UuidInterface $id;

    private ?string $payload;

    private DateTimeInterface $occurredAt;

    private string $screenName;

    private string $atCursor;

    private DateTimeInterface $startedAt;

    private ?DateTimeInterface $endedAt;

    public function __construct(
        string $screenName,
        string $atCursor,
        DateTimeInterface $occurredAt,
        DateTimeInterface $startedAt,
        ?string $payload = null,
        ?DateTimeInterface $endedAt = null
    ) {
        $this->screenName = $screenName;
        $this->atCursor   = $atCursor;
        $this->payload    = $payload;
        $this->occurredAt = $occurredAt;
        $this->startedAt  = $startedAt;
        $this->endedAt    = $endedAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
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
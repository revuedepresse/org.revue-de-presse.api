<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class OwnershipBatchCollectedEvent
{
    private UuidInterface $id;

    private ?string $payload;

    private DateTimeInterface $occurredAt;

    private string $screenName;

    private DateTimeInterface $startedAt;

    private ?DateTimeInterface $endedAt;

    public function __construct(
        string $screenName,
        DateTimeInterface $occurredAt,
        DateTimeInterface $startedAt,
        ?string $payload = null,
        ?DateTimeInterface $endedAt = null
    ) {
        $this->screenName     = $screenName;
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
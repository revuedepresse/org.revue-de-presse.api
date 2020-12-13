<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class PublishersListCollectedEvent
{
    private UuidInterface $id;

    private ?string $payload;

    private DateTimeInterface $occurredAt;

    private int $listId;

    private DateTimeInterface $startedAt;

    private ?DateTimeInterface $endedAt;

    private string $listName;

    public function __construct(
        int $listId,
        string $listName,
        DateTimeInterface $occurredAt,
        DateTimeInterface $startedAt,
        ?string $payload = null,
        ?DateTimeInterface $endedAt = null
    ) {
        $this->listId     = $listId;
        $this->listName     = $listName;
        $this->payload    = $payload;
        $this->occurredAt = $occurredAt;
        $this->startedAt  = $startedAt;
        $this->endedAt    = $endedAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function listId(): int
    {
        return $this->listId;
    }

    public function listName(): string
    {
        return $this->listName;
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
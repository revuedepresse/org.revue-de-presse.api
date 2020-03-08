<?php
declare(strict_types=1);

namespace App\Domain\Collect\FetchPublicationStarted;

use Ramsey\Uuid\UuidInterface;

class CollectPublicationEvent
{
    private UuidInterface $id;

    private string $type;

    private string $payload;

    private \DateTimeInterface $occurredAt;

    public function __construct(
        string $type,
        string $payload,
        \DateTimeInterface $occurredAt
    ) {
        $this->type = $type;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function occurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }
}
<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use App\Membership\Domain\Entity\MemberInterface;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class PublicationBatchCollectedEvent
{
    private UuidInterface $id;

    private ?string $payload;

    private DateTimeInterface $occurredAt;

    private MemberInterface $member;

    private DateTimeInterface $startedAt;

    private ?DateTimeInterface $endedAt;

    public function __construct(
        MemberInterface $member,
        DateTimeInterface $occurredAt,
        DateTimeInterface $startedAt,
        ?string $payload = null,
        ?DateTimeInterface $endedAt = null
    ) {
        $this->member     = $member;
        $this->payload    = $payload;
        $this->occurredAt = $occurredAt;
        $this->startedAt  = $startedAt;
        $this->endedAt    = $endedAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function member(): MemberInterface
    {
        return $this->member;
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
<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use App\Twitter\Infrastructure\Twitter\Api\Selector\ListSelector;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

interface ListCollectedEvent
{
    public function __construct(
        ListSelector $selector,
        DateTimeInterface $occurredAt,
        DateTimeInterface $startedAt,
        ?string $payload = null,
        ?DateTimeInterface $endedAt = null
    );

    public function id(): UuidInterface;
    public function correlationId(): UuidInterface;
    public function screenName(): string;
    public function atCursor(): string;
    public function payload(): string;
    public function startedAt(): DateTimeInterface;
    public function endedAt(): DateTimeInterface;
    public function occurredAt(): DateTimeInterface;

    public function finishCollect(string $payload): self;
}
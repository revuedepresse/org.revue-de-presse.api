<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use App\Twitter\Infrastructure\Twitter\Api\Selector\FollowersListSelector;
use App\Twitter\Infrastructure\Twitter\Api\Selector\ListSelector;
use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\Rfc4122\UuidV4;
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
        $this->endedAt = new DateTimeImmutable();

        return $this;
    }

    public function serialize(): string
    {
        return json_encode([
            'payload' => $this->payload(),
            'correlation_id' => $this->screenName(),
            'screen_name' => $this->screenName(),
            'cursor' => $this->atCursor(),
            'occurred_at' => $this->occurredAt()->format(\DateTimeInterface::ATOM),
            'ended_at' => $this->occurredAt()->format(\DateTimeInterface::ATOM),
            'started_at' => $this->occurredAt()->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);
    }

    public static function unserialize(string $serializedEvent): self
    {
        $decodedSerializedEvent = json_decode($serializedEvent, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            new FollowersListSelector(
                UuidV4::fromString($decodedSerializedEvent['correlation_id']),
                $decodedSerializedEvent['screen_name'],
                $decodedSerializedEvent['cursor']
            ),
            new DateTimeImmutable($decodedSerializedEvent['occurred_at']),
            new DateTimeImmutable($decodedSerializedEvent['started_at']),
            $decodedSerializedEvent['payload'],
            new DateTimeImmutable($decodedSerializedEvent['ended_at'])
        );
    }
}

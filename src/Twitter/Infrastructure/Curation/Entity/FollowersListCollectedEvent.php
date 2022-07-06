<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Entity;

use App\Twitter\Domain\Curation\Entity\JsonSerializableInterface;
use App\Twitter\Domain\Curation\Entity\ListCollectedEvent;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdAwareInterface;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationIdInterface;
use App\Twitter\Infrastructure\Http\Selector\FollowersListSelector;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

class FollowersListCollectedEvent implements ListCollectedEvent, JsonSerializableInterface
{
    private UuidInterface $id;

    private CorrelationIdInterface $correlationId;

    private ?string $payload;

    private DateTimeInterface $occurredAt;

    private string $screenName;

    private string $atCursor;

    private DateTimeInterface $startedAt;

    private ?DateTimeInterface $endedAt;

    public function __construct(
        ListSelectorInterface $selector,
        DateTimeInterface $occurredAt,
        DateTimeInterface $startedAt,
        ?string $payload = null,
        ?DateTimeInterface $endedAt = null
    ) {
        $this->screenName    = $selector->screenName();
        $this->atCursor      = $selector->cursor();
        $this->payload       = $payload;
        $this->occurredAt    = $occurredAt;
        $this->startedAt     = $startedAt;
        $this->endedAt       = $endedAt;

        if ($selector instanceof CorrelationIdAwareInterface) {
            $this->correlationId = $selector->correlationId();

            return;
        }

        $this->correlationId = CorrelationId::generate();
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function correlationId(): CorrelationIdInterface
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

    public function jsonSerialize(): string
    {
        return json_encode([
            'payload' => $this->payload(),
            'correlation_id' => $this->correlationId()->asString(),
            'screen_name' => $this->screenName(),
            'cursor' => $this->atCursor(),
            'occurred_at' => $this->occurredAt()->format(\DateTimeInterface::ATOM),
            'ended_at' => $this->occurredAt()->format(\DateTimeInterface::ATOM),
            'started_at' => $this->occurredAt()->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);
    }

    public static function jsonDeserialize(string $serializedSubject): JsonSerializableInterface
    {
        $decodedSerializedEvent = json_decode($serializedSubject, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            new FollowersListSelector(
                $decodedSerializedEvent['screen_name'],
                $decodedSerializedEvent['cursor'],
                CorrelationId::fromString($decodedSerializedEvent['correlation_id'])
            ),
            new DateTimeImmutable($decodedSerializedEvent['occurred_at']),
            new DateTimeImmutable($decodedSerializedEvent['started_at']),
            $decodedSerializedEvent['payload'],
            new DateTimeImmutable($decodedSerializedEvent['ended_at'])
        );
    }
}

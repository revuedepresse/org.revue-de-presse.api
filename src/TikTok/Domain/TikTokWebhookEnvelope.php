<?php
declare(strict_types=1);

namespace App\TikTok\Domain;

use InvalidArgumentException;

/**
 * Envelope shared by every TikTok Events delivery (the event identifier,
 * the issuing `client_key`, `create_time`, the target user, and an opaque
 * `content` payload whose shape varies per event). Only the envelope is
 * validated at the edge; event-specific parsing is left to downstream
 * handlers.
 *
 * Mirrors `TikTokWebhookEnvelopeSchema` on the NestJS side.
 */
final readonly class TikTokWebhookEnvelope
{
    public function __construct(
        public string $event,
        public string $client_key,
        public int $create_time,
        public ?string $user_openid,
        public mixed $content,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $event = $payload['event'] ?? null;
        if (!is_string($event) || $event === '') {
            throw new InvalidArgumentException('event: must be a non-empty string');
        }

        $clientKey = $payload['client_key'] ?? null;
        if (!is_string($clientKey) || $clientKey === '') {
            throw new InvalidArgumentException('client_key: must be a non-empty string');
        }

        $createTime = $payload['create_time'] ?? null;
        if (!is_int($createTime)) {
            throw new InvalidArgumentException('create_time: must be an integer');
        }

        $userOpenId = $payload['user_openid'] ?? null;
        if ($userOpenId !== null && !is_string($userOpenId)) {
            throw new InvalidArgumentException('user_openid: must be a string when present');
        }

        return new self(
            $event,
            $clientKey,
            $createTime,
            $userOpenId,
            $payload['content'] ?? null,
        );
    }
}

<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use RuntimeException;

/**
 * Thrown when an inbound TikTok webhook fails signature verification: the
 * `TikTok-Signature` header is missing, malformed, has a stale or non-numeric
 * timestamp, fails the HMAC compare, or carries a body that is not a valid
 * envelope. The controller maps this to a `401 application/problem+json`.
 */
final class TikTokWebhookVerificationException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('TikTok webhook verification failed: ' . $reason);
    }
}

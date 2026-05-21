<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use RuntimeException;

/**
 * Thrown when the TikTok webhook verifier is invoked without a configured
 * `TIKTOK_CLIENT_SECRET`. The controller maps this to a `503
 * application/problem+json` so non-TikTok deployments still boot cleanly.
 */
final class UnconfiguredTikTokWebhookException extends RuntimeException
{
    public static function withDefaultMessage(): self
    {
        return new self('TikTok webhook secret not configured on the server');
    }
}

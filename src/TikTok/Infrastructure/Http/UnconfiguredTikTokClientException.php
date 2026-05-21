<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use RuntimeException;

/**
 * Thrown when `TIKTOK_CLIENT_KEY` or `TIKTOK_CLIENT_SECRET` is missing /
 * empty at the moment a TikTok exchange is attempted. The controller maps
 * this to a `503 application/problem+json` response so non-TikTok
 * deployments still boot cleanly.
 */
final class UnconfiguredTikTokClientException extends RuntimeException
{
    public static function withDefaultMessage(): self
    {
        return new self('TikTok credentials not configured on the server');
    }
}

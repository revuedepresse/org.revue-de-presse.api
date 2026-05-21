<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use RuntimeException;

/**
 * Thrown by `HttpTikTokOAuthClient` whenever TikTok rejects the
 * `authorization_code` exchange — either with a non-2xx response, or with
 * a 2xx body containing an `error` field (TikTok's documented quirk).
 *
 * The raw upstream body is exposed via `getDetail()` so the controller can
 * surface the TikTok-side description back to the maintainer verbatim.
 */
final class TikTokExchangeException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        private readonly string $detail,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('TikTok rejected the authorization_code exchange (status %d)', $statusCode),
            0,
            $previous,
        );
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * @param array<string, mixed>|string|null $body
     */
    public static function fromUpstream(int $statusCode, array|string|null $body): self
    {
        if (is_array($body)) {
            $encoded = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $detail = $encoded === false ? '' : $encoded;
        } elseif (is_string($body)) {
            $detail = $body;
        } else {
            $detail = '';
        }

        if ($detail === '') {
            $detail = sprintf('TikTok returned status %d with no parsable body', $statusCode);
        }

        return new self($statusCode, $detail);
    }

    public static function invalidResponseShape(string $reason): self
    {
        return new self(400, sprintf('Unexpected TikTok token response shape: %s', $reason));
    }
}

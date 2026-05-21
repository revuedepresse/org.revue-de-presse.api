<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use App\TikTok\Domain\TikTokWebhookEnvelope;
use Closure;
use InvalidArgumentException;
use JsonException;

/**
 * Production TikTok webhook verifier. TikTok signs each delivery with a
 * `TikTok-Signature` header of the form `t=<unix-seconds>,s=<hex>`, where
 * the hex is HMAC-SHA256 of `"{t}.{raw-body}"` keyed by the app's
 * client_secret. We recompute the MAC and compare in constant time, and
 * reject signatures whose timestamp is more than {@see MAX_AGE_SECONDS}
 * old to bound the replay window.
 *
 * Mirrors `HmacTikTokWebhookVerifier` on the NestJS side.
 */
final class HmacTikTokWebhookVerifier implements TikTokWebhookVerifier
{
    public const int MAX_AGE_SECONDS = 300;

    /** @var Closure(): int */
    private readonly Closure $now;

    /**
     * @param (Closure(): int)|null $now Override for the current Unix time —
     *                                   only used by the verifier's own tests.
     */
    public function __construct(
        private readonly ?string $clientSecret,
        ?Closure $now = null,
    ) {
        $this->now = $now ?? static fn (): int => time();
    }

    public function verifyAndParse(?string $signatureHeader, string $rawBody): TikTokWebhookEnvelope
    {
        if ($this->clientSecret === null || $this->clientSecret === '') {
            throw UnconfiguredTikTokWebhookException::withDefaultMessage();
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            throw new TikTokWebhookVerificationException('missing TikTok-Signature header');
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            $eq = strpos($segment, '=');
            if ($eq === false) {
                continue;
            }
            $parts[trim(substr($segment, 0, $eq))] = trim(substr($segment, $eq + 1));
        }

        $t = $parts['t'] ?? '';
        $s = $parts['s'] ?? '';
        if ($t === '' || $s === '') {
            throw new TikTokWebhookVerificationException('malformed TikTok-Signature header');
        }

        if (preg_match('/^-?\d+$/', $t) !== 1) {
            throw new TikTokWebhookVerificationException('non-numeric signature timestamp');
        }
        $timestamp = (int) $t;
        if (abs(($this->now)() - $timestamp) > self::MAX_AGE_SECONDS) {
            throw new TikTokWebhookVerificationException('signature timestamp out of window');
        }

        if (preg_match('/^[0-9a-f]+$/i', $s) !== 1) {
            throw new TikTokWebhookVerificationException('non-hex signature');
        }

        $expectedHex = hash_hmac('sha256', $t . '.' . $rawBody, $this->clientSecret);
        $expected = hex2bin($expectedHex);
        $provided = hex2bin($s);
        if (
            $expected === false
            || $provided === false
            || strlen($expected) === 0
            || strlen($expected) !== strlen($provided)
            || !hash_equals($expected, $provided)
        ) {
            throw new TikTokWebhookVerificationException('signature mismatch');
        }

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new TikTokWebhookVerificationException('body is not valid JSON');
        }
        if (!is_array($decoded)) {
            throw new TikTokWebhookVerificationException('body is not a JSON object');
        }

        try {
            return TikTokWebhookEnvelope::fromArray($decoded);
        } catch (InvalidArgumentException $e) {
            throw new TikTokWebhookVerificationException('malformed event envelope: ' . $e->getMessage());
        }
    }
}

<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use App\TikTok\Domain\TikTokWebhookEnvelope;

/**
 * Inbound seam for TikTok webhook signature verification.
 *
 * The production implementation recomputes the HMAC the platform attaches
 * via the `TikTok-Signature` header and parses the validated body into a
 * {@see TikTokWebhookEnvelope}. Tests bind a stub instead so they do not
 * have to compute real HMACs.
 */
interface TikTokWebhookVerifier
{
    /**
     * @throws UnconfiguredTikTokWebhookException If the verifier was wired
     *                                            without a client_secret.
     * @throws TikTokWebhookVerificationException If the signature is missing,
     *                                            malformed, stale, mismatched,
     *                                            or the body envelope is invalid.
     */
    public function verifyAndParse(?string $signatureHeader, string $rawBody): TikTokWebhookEnvelope;
}

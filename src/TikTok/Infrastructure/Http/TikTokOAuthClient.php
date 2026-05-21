<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use App\TikTok\Domain\TikTokTokenResponse;

/**
 * Outbound seam for the TikTok OAuth token endpoint.
 *
 * Implementations are expected to call
 * `POST https://open.tiktokapis.com/v2/oauth/token/` with
 * `grant_type=authorization_code`. Tests bind a `MockHttpClient`-backed
 * implementation instead of `HttpTikTokOAuthClient` so no live HTTP call
 * is made from the suite.
 */
interface TikTokOAuthClient
{
    /**
     * @throws UnconfiguredTikTokClientException If client_key/client_secret are missing.
     * @throws TikTokExchangeException           If TikTok rejects the exchange (non-2xx
     *                                           or 2xx with an `error` field).
     */
    public function exchangeCode(string $code, string $codeVerifier, string $redirectUri): TikTokTokenResponse;
}

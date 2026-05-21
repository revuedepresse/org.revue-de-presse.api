<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Http;

use App\TikTok\Domain\TikTokTokenResponse;
use InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Production implementation of {@see TikTokOAuthClient}.
 *
 * Posts a form-encoded body to TikTok's token endpoint via Symfony's
 * `HttpClientInterface`. The HTTP client is constructor-injected so tests can
 * swap in a `MockHttpClient` without hitting the network.
 *
 * Credentials are read from env vars `TIKTOK_CLIENT_KEY` /
 * `TIKTOK_CLIENT_SECRET`; empty strings count as "unconfigured" so non-TikTok
 * deployments still parse + boot.
 */
final class HttpTikTokOAuthClient implements TikTokOAuthClient
{
    private const TIKTOK_TOKEN_ENDPOINT = 'https://open.tiktokapis.com/v2/oauth/token/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $clientKey,
        private readonly string $clientSecret,
        private readonly string $endpoint = self::TIKTOK_TOKEN_ENDPOINT,
    ) {
    }

    public function exchangeCode(string $code, string $codeVerifier, string $redirectUri): TikTokTokenResponse
    {
        if ($this->clientKey === '' || $this->clientSecret === '') {
            throw UnconfiguredTikTokClientException::withDefaultMessage();
        }

        $body = http_build_query([
            'client_key'    => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri,
            'code_verifier' => $codeVerifier,
        ], '', '&', PHP_QUERY_RFC3986);

        try {
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'headers' => [
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Cache-Control' => 'no-cache',
                ],
                'body' => $body,
            ]);

            $status = $response->getStatusCode();
            $rawBody = $response->getContent(false);
        } catch (ExceptionInterface $e) {
            throw new TikTokExchangeException(
                502,
                sprintf('TikTok transport error: %s', $e->getMessage()),
                $e,
            );
        }

        $payload = $this->safeJson($rawBody);

        if ($status < 200 || $status >= 300) {
            throw TikTokExchangeException::fromUpstream($status, $payload ?? $rawBody);
        }

        // TikTok occasionally returns 200 with an `error` field instead of
        // tokens; treat that as an exchange failure.
        if (is_array($payload) && array_key_exists('error', $payload)) {
            throw TikTokExchangeException::fromUpstream(400, $payload);
        }

        if (!is_array($payload)) {
            throw TikTokExchangeException::invalidResponseShape('upstream body is not a JSON object');
        }

        try {
            return TikTokTokenResponse::fromArray($payload);
        } catch (InvalidArgumentException $e) {
            throw TikTokExchangeException::invalidResponseShape($e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeJson(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}

<?php
declare(strict_types=1);

namespace App\Tests\TikTok\Infrastructure\Http;

use App\TikTok\Infrastructure\Http\HttpTikTokOAuthClient;
use App\TikTok\Infrastructure\Http\TikTokExchangeException;
use App\TikTok\Infrastructure\Http\UnconfiguredTikTokClientException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Pure unit suite for the HTTP TikTok client. Boots no kernel — drives
 * {@see HttpTikTokOAuthClient} directly against `MockHttpClient` so we can
 * assert on the captured method, URL and form body bytes.
 *
 * @group unit
 */
class HttpTikTokOAuthClientTest extends TestCase
{
    public function test_posts_form_encoded_body_and_returns_dto_on_happy_path(): void
    {
        $payload = [
            'access_token'       => 'tt-access-xyz',
            'refresh_token'      => 'tt-refresh-xyz',
            'expires_in'         => 86400,
            'refresh_expires_in' => 31536000,
            'scope'              => 'video.upload',
            'open_id'            => 'open_abc',
        ];

        $captured = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured, $payload): MockResponse {
            $captured = ['method' => $method, 'url' => $url, 'options' => $options];

            return new MockResponse(
                (string) json_encode($payload),
                ['http_code' => 200, 'response_headers' => ['Content-Type' => 'application/json']],
            );
        });

        $client = new HttpTikTokOAuthClient($http, 'key123', 'sec456');
        $out = $client->exchangeCode(
            'auth-code-1',
            'verifier-1',
            'https://api.revue-de-presse.org/api/tiktok/oauth/callback',
        );

        self::assertSame('POST', $captured['method']);
        self::assertSame('https://open.tiktokapis.com/v2/oauth/token/', $captured['url']);

        // Headers reach MockHttpClient as a normalized list.
        $headers = $captured['options']['headers'] ?? [];
        $joined = implode("\n", array_map(static fn ($h) => is_array($h) ? implode(',', $h) : (string) $h, $headers));
        self::assertStringContainsString('application/x-www-form-urlencoded', $joined);

        $body = (string) ($captured['options']['body'] ?? '');
        parse_str($body, $form);
        self::assertSame('key123', $form['client_key']);
        self::assertSame('sec456', $form['client_secret']);
        self::assertSame('authorization_code', $form['grant_type']);
        self::assertSame('auth-code-1', $form['code']);
        self::assertSame('verifier-1', $form['code_verifier']);
        self::assertSame(
            'https://api.revue-de-presse.org/api/tiktok/oauth/callback',
            $form['redirect_uri'],
        );

        self::assertSame('tt-access-xyz', $out->access_token);
        self::assertSame('tt-refresh-xyz', $out->refresh_token);
        self::assertSame(86400, $out->expires_in);
        self::assertSame(31536000, $out->refresh_expires_in);
        self::assertSame('open_abc', $out->open_id);
        self::assertSame($payload, $out->toArray());
    }

    public function test_throws_exchange_exception_on_upstream_4xx(): void
    {
        $http = new MockHttpClient(new MockResponse(
            (string) json_encode([
                'error'             => 'invalid_grant',
                'error_description' => 'Authorization code expired.',
            ]),
            ['http_code' => 400, 'response_headers' => ['Content-Type' => 'application/json']],
        ));

        $client = new HttpTikTokOAuthClient($http, 'k', 's');

        try {
            $client->exchangeCode('expired', 'v', 'https://example.test/cb');
            self::fail('Expected TikTokExchangeException');
        } catch (TikTokExchangeException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertStringContainsString('invalid_grant', $e->getDetail());
        }
    }

    public function test_throws_exchange_exception_when_2xx_body_contains_error_field(): void
    {
        // TikTok's documented quirk: occasional 200 with an `error` field
        // rather than tokens.
        $http = new MockHttpClient(new MockResponse(
            (string) json_encode([
                'error'             => 'invalid_request',
                'error_description' => 'Missing code_verifier',
            ]),
            ['http_code' => 200, 'response_headers' => ['Content-Type' => 'application/json']],
        ));

        $client = new HttpTikTokOAuthClient($http, 'k', 's');

        try {
            $client->exchangeCode('c', 'v', 'https://example.test/cb');
            self::fail('Expected TikTokExchangeException');
        } catch (TikTokExchangeException $e) {
            self::assertStringContainsString('invalid_request', $e->getDetail());
        }
    }

    public function test_throws_exchange_exception_when_2xx_body_does_not_match_token_shape(): void
    {
        $http = new MockHttpClient(new MockResponse(
            (string) json_encode(['something' => 'unexpected']),
            ['http_code' => 200, 'response_headers' => ['Content-Type' => 'application/json']],
        ));

        $client = new HttpTikTokOAuthClient($http, 'k', 's');

        try {
            $client->exchangeCode('c', 'v', 'https://example.test/cb');
            self::fail('Expected TikTokExchangeException');
        } catch (TikTokExchangeException $e) {
            self::assertStringContainsString('Unexpected TikTok token response shape', $e->getDetail());
        }
    }

    public function test_throws_unconfigured_exception_when_client_key_missing(): void
    {
        $http = new MockHttpClient([]);
        $client = new HttpTikTokOAuthClient($http, '', 'sec456');

        $this->expectException(UnconfiguredTikTokClientException::class);
        $client->exchangeCode('c', 'v', 'https://example.test/cb');
    }
}

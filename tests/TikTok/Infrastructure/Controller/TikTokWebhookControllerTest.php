<?php
declare(strict_types=1);

namespace App\Tests\TikTok\Infrastructure\Controller;

use App\TikTok\Domain\TikTokWebhookEnvelope;
use App\TikTok\Infrastructure\Http\TikTokWebhookVerificationException;
use App\TikTok\Infrastructure\Http\TikTokWebhookVerifier;
use App\TikTok\Infrastructure\Http\UnconfiguredTikTokWebhookException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group http
 */
class TikTokWebhookControllerTest extends WebTestCase
{
    // `.env.test` ships TIKTOK_CLIENT_SECRET=test-secret, so the wired
    // `HmacTikTokWebhookVerifier` will recompute MACs against that key.
    private const SECRET = 'test-secret';

    public function test_returns_200_with_ok_true_when_signature_is_valid(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);

        $body = (string) json_encode([
            'event'       => 'video.publish.complete',
            'client_key'  => 'client_key_abc',
            'create_time' => 1_700_000_000,
            'user_openid' => 'open-1',
        ]);
        $now = time();
        $sig = hash_hmac('sha256', $now . '.' . $body, self::SECRET);

        $client->request(
            'POST',
            '/api/tiktok/webhook/callback',
            server: [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_TIKTOK_SIGNATURE' => 't=' . $now . ',s=' . $sig,
            ],
            content: $body,
        );

        $response = $client->getResponse();
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());
        self::assertSame(['ok' => true], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_returns_401_problem_json_when_signature_is_missing(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);

        $client->request(
            'POST',
            '/api/tiktok/webhook/callback',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{}',
        );

        $response = $client->getResponse();
        self::assertSame(401, $response->getStatusCode(), (string) $response->getContent());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );
    }

    public function test_returns_401_problem_json_when_signature_does_not_match(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);

        $client->request(
            'POST',
            '/api/tiktok/webhook/callback',
            server: [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_TIKTOK_SIGNATURE' => 't=' . time() . ',s=' . str_repeat('a', 64),
            ],
            content: '{"event":"e","client_key":"k","create_time":1}',
        );

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function test_returns_503_problem_json_when_secret_is_not_configured(): void
    {
        // The %env(...)% bindings are baked into the compiled container, so
        // env mutation between requests is fragile. Instead we swap the wired
        // verifier with a stub that raises the same exception
        // `HmacTikTokWebhookVerifier` would raise when the secret is missing.
        $client = static::createClient();
        $client->catchExceptions(true);
        $client->disableReboot();

        static::getContainer()->set(TikTokWebhookVerifier::class, new class implements TikTokWebhookVerifier {
            public function verifyAndParse(?string $signatureHeader, string $rawBody): TikTokWebhookEnvelope
            {
                throw UnconfiguredTikTokWebhookException::withDefaultMessage();
            }
        });

        $client->request(
            'POST',
            '/api/tiktok/webhook/callback',
            server: [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_TIKTOK_SIGNATURE' => 't=1,s=00',
            ],
            content: '{}',
        );

        $response = $client->getResponse();
        self::assertSame(503, $response->getStatusCode(), (string) $response->getContent());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('TikTok webhook secret not configured', $payload['detail']);
    }

    public function test_propagates_verification_reason_in_problem_detail(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);
        $client->disableReboot();

        static::getContainer()->set(TikTokWebhookVerifier::class, new class implements TikTokWebhookVerifier {
            public function verifyAndParse(?string $signatureHeader, string $rawBody): TikTokWebhookEnvelope
            {
                throw new TikTokWebhookVerificationException('signature mismatch');
            }
        });

        $client->request(
            'POST',
            '/api/tiktok/webhook/callback',
            server: [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_TIKTOK_SIGNATURE' => 't=1,s=00',
            ],
            content: '{}',
        );

        $response = $client->getResponse();
        self::assertSame(401, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('signature mismatch', $payload['detail']);
    }

    public function test_is_publicly_reachable_without_authorization_header(): void
    {
        // No `HTTP_AUTHORIZATION` server param set — proves the access_control
        // PUBLIC_ACCESS rule for `^/api/tiktok/webhook/callback` is in place.
        // A missing signature surfaces as 401 from the controller, not from
        // the firewall.
        $client = static::createClient();
        $client->catchExceptions(true);

        $client->request(
            'POST',
            '/api/tiktok/webhook/callback',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{}',
        );

        $response = $client->getResponse();
        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );
    }
}

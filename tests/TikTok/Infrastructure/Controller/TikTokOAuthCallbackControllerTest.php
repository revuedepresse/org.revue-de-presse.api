<?php
declare(strict_types=1);

namespace App\Tests\TikTok\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group http
 */
class TikTokOAuthCallbackControllerTest extends WebTestCase
{
    public function test_renders_html_page_when_code_and_state_are_present(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/tiktok/oauth/callback', [
            'code'  => 'auth-code-abc',
            'state' => 'state-xyz',
        ]);

        $response = $client->getResponse();
        $body = (string) $response->getContent();

        self::assertSame(200, $response->getStatusCode(), $body);
        self::assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertStringContainsString('auth-code-abc', $body);
        self::assertStringContainsString('state-xyz', $body);
        self::assertStringContainsString('Paste this URL back into the bootstrap CLI', $body);
        self::assertStringContainsString('<textarea readonly', $body);
    }

    public function test_html_escapes_user_controlled_code_and_state(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/tiktok/oauth/callback', [
            'code'  => '<script>alert(1)</script>',
            'state' => 'st"ate',
        ]);

        $response = $client->getResponse();
        $body = (string) $response->getContent();

        self::assertSame(200, $response->getStatusCode(), $body);
        self::assertStringNotContainsString('<script>alert(1)</script>', $body);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
        self::assertStringContainsString('st&quot;ate', $body);
    }

    public function test_returns_problem_json_400_when_code_is_missing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/tiktok/oauth/callback', ['state' => 'st']);

        $response = $client->getResponse();
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertMatchesRegularExpression('/code.*state|state.*code/i', $payload['detail']);
    }

    public function test_returns_problem_json_400_when_state_is_missing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/tiktok/oauth/callback', ['code' => 'c']);

        $response = $client->getResponse();
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );
    }

    public function test_returns_problem_json_400_when_error_param_is_present(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/tiktok/oauth/callback', [
            'error'             => 'access_denied',
            'error_description' => 'user cancelled',
        ]);

        $response = $client->getResponse();
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('access_denied', $payload['detail']);
        self::assertStringContainsString('user cancelled', $payload['detail']);
    }

    public function test_is_publicly_reachable_without_authorization_header(): void
    {
        $client = static::createClient();
        // No `HTTP_AUTHORIZATION` server param set — proves the access_control
        // PUBLIC_ACCESS rule for `^/api/tiktok/oauth/callback` is in place.
        $client->request('GET', '/api/tiktok/oauth/callback', [
            'code'  => 'c',
            'state' => 's',
        ]);

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}

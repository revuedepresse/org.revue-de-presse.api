<?php
declare(strict_types=1);

namespace App\Tests\TikTok\Infrastructure\Controller;

use App\Membership\Domain\Entity\Member;
use App\TikTok\Domain\TikTokTokenResponse;
use App\TikTok\Infrastructure\Http\TikTokOAuthClient;
use App\TikTok\Infrastructure\Http\UnconfiguredTikTokClientException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @group http
 */
class TikTokOAuthExchangeControllerTest extends WebTestCase
{
    private const SECRET = 'dummy-test-secret';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        // The exchange endpoint is gated behind the existing access_token
        // firewall, so each case mints a Bearer the same way TokenResourceTest
        // does. Test env (.env.test) ships TIKTOK_CLIENT_KEY=test-key /
        // _SECRET=test-secret as the defaults; the missing-credentials case
        // swaps the wired client at the service level instead.
        $this->client = static::createClient();
        $this->client->catchExceptions(true);
        $this->client->disableReboot();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if ($metadata !== []) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        $member = new Member();
        $member->apiKey = self::SECRET;
        $member->setEnabled(true);
        $member->setUsername('test-user');
        $em->persist($member);
        $em->flush();

        // Reset the MockHttpClient between tests by replacing its response
        // factory with a fresh empty queue.
        $this->resetMockHttp([]);
    }

    public function test_happy_path_returns_200_with_upstream_tokens(): void
    {
        $bearer = $this->mintToken();
        $payload = [
            'access_token'       => 'tt-access',
            'refresh_token'      => 'tt-refresh',
            'expires_in'         => 86400,
            'refresh_expires_in' => 31536000,
            'scope'              => 'video.upload',
            'open_id'            => 'open_42',
        ];

        $capturedBody = null;
        $this->resetMockHttp([
            function (string $method, string $url, array $options) use (&$capturedBody, $payload): MockResponse {
                $capturedBody = (string) ($options['body'] ?? '');
                self::assertSame('POST', $method);
                self::assertSame('https://open.tiktokapis.com/v2/oauth/token/', $url);

                return new MockResponse((string) json_encode($payload), [
                    'http_code'        => 200,
                    'response_headers' => ['Content-Type' => 'application/json'],
                ]);
            },
        ]);

        $this->client->request(
            'POST',
            '/api/tiktok/oauth/exchange',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $bearer,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: (string) json_encode([
                'code'          => 'auth-code-1',
                'code_verifier' => 'verifier-1',
                'redirect_uri'  => 'https://api.revue-de-presse.org/api/tiktok/oauth/callback',
            ]),
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('tt-access', $body['access_token']);
        self::assertSame('tt-refresh', $body['refresh_token']);
        self::assertSame(86400, $body['expires_in']);
        self::assertSame('open_42', $body['open_id']);

        self::assertNotNull($capturedBody);
        parse_str($capturedBody, $form);
        self::assertSame('test-key', $form['client_key']);
        self::assertSame('test-secret', $form['client_secret']);
        self::assertSame('auth-code-1', $form['code']);
        self::assertSame('verifier-1', $form['code_verifier']);
        self::assertSame('authorization_code', $form['grant_type']);
    }

    public function test_upstream_4xx_returns_400_problem_json(): void
    {
        $bearer = $this->mintToken();
        $this->resetMockHttp([
            new MockResponse(
                (string) json_encode([
                    'error'             => 'invalid_grant',
                    'error_description' => 'Authorization code expired.',
                ]),
                ['http_code' => 400, 'response_headers' => ['Content-Type' => 'application/json']],
            ),
        ]);

        $this->client->request(
            'POST',
            '/api/tiktok/oauth/exchange',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $bearer,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: (string) json_encode([
                'code'          => 'c',
                'code_verifier' => 'v',
                'redirect_uri'  => 'https://x.test/cb',
            ]),
        );

        $response = $this->client->getResponse();
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('invalid_grant', $body['detail']);
    }

    public function test_malformed_json_body_returns_400_problem_json(): void
    {
        $bearer = $this->mintToken();

        $this->client->request(
            'POST',
            '/api/tiktok/oauth/exchange',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $bearer,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: '{not json',
        );

        $response = $this->client->getResponse();
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );
    }

    public function test_no_bearer_returns_401(): void
    {
        $this->client->request(
            'POST',
            '/api/tiktok/oauth/exchange',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'code'          => 'c',
                'code_verifier' => 'v',
                'redirect_uri'  => 'https://x.test/cb',
            ]),
        );

        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function test_missing_credentials_returns_503_problem_json(): void
    {
        // The %env(...)% bindings are baked into the compiled container, so
        // env mutation between requests is fragile. Instead we replace the
        // wired TikTokOAuthClient with a stub that throws the same exception
        // `HttpTikTokOAuthClient` would throw when client_key/client_secret
        // are missing. The controller's 503 mapping is what we want to cover.
        $bearer = $this->mintToken();

        static::getContainer()->set(TikTokOAuthClient::class, new class implements TikTokOAuthClient {
            public function exchangeCode(string $code, string $codeVerifier, string $redirectUri): TikTokTokenResponse
            {
                throw UnconfiguredTikTokClientException::withDefaultMessage();
            }
        });

        $this->client->request(
            'POST',
            '/api/tiktok/oauth/exchange',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $bearer,
                'CONTENT_TYPE'       => 'application/json',
            ],
            content: (string) json_encode([
                'code'          => 'c',
                'code_verifier' => 'v',
                'redirect_uri'  => 'https://x.test/cb',
            ]),
        );

        $response = $this->client->getResponse();
        self::assertSame(503, $response->getStatusCode(), (string) $response->getContent());
        self::assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type'),
        );
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('TikTok credentials not configured', $body['detail']);
    }

    private function mintToken(): string
    {
        $this->client->request(
            'POST',
            '/api/token',
            server: ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(':' . self::SECRET)],
        );
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!isset($body['access_token'])) {
            self::fail(sprintf(
                'Mint failed with status %d, body: %s',
                $this->client->getResponse()->getStatusCode(),
                json_encode($body),
            ));
        }

        return $body['access_token'];
    }

    /**
     * @param list<MockResponse|callable> $responses
     */
    private function resetMockHttp(array $responses): void
    {
        /** @var MockHttpClient $mock */
        $mock = static::getContainer()->get('app.test.tiktok_mock_http_client');
        $mock->setResponseFactory($responses);
    }
}

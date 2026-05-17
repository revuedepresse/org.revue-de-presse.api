<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\ApiPlatform\State;

use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group http
 */
class HighlightsResourceTest extends WebTestCase
{
    private const SECRET = 'dummy-test-secret';

    private KernelBrowser $client;

    protected function setUp(): void
    {
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
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->client->request('GET', '/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0');
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function test_authenticated_get_returns_hydra_collection(): void
    {
        $bearer = $this->mintToken();

        $this->client->request(
            'GET',
            '/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $bearer, 'HTTP_ACCEPT' => 'application/ld+json'],
        );

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode(), $response->getContent() ?: 'no body');
        self::assertStringContainsString('application/ld+json', (string) $response->headers->get('Content-Type'));

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Collection', $body['@type'] ?? null);
        self::assertArrayHasKey('member', $body);
        self::assertArrayHasKey('totalItems', $body);
    }

    public function test_response_has_cache_control_max_age_3600(): void
    {
        $bearer = $this->mintToken();

        $this->client->request(
            'GET',
            '/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $bearer],
        );

        self::assertStringContainsString('max-age=3600', (string) $this->client->getResponse()->headers->get('Cache-Control'));
    }

    public function test_response_has_vary_header(): void
    {
        $bearer = $this->mintToken();
        $this->client->request(
            'GET',
            '/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $bearer],
        );

        // AP `cacheHeaders.vary` should produce a Vary header; exact value
        // depends on AP version and CORS bundle interplay. The cache discipline
        // (different bearer => potentially different cached body) is enforced
        // upstream by Authorization being part of the request signature.
        self::assertNotNull(
            $this->client->getResponse()->headers->get('Vary'),
            'Expected a Vary header to be set',
        );
    }

    public function test_bogus_bearer_returns_401(): void
    {
        $this->client->request(
            'GET',
            '/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . str_repeat('a', 64)],
        );
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    private function mintToken(): string
    {
        $this->client->request(
            'POST',
            '/api/token',
            server: ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(':' . self::SECRET)],
        );
        $status = $this->client->getResponse()->getStatusCode();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!isset($body['access_token'])) {
            self::fail(sprintf('Mint failed with status %d, body: %s', $status, json_encode($body)));
        }

        return $body['access_token'];
    }
}

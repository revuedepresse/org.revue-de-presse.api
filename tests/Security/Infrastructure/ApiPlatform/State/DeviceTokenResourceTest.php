<?php
declare(strict_types=1);

namespace App\Tests\Security\Infrastructure\ApiPlatform\State;

use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * End-to-end check for POST /api/device-tokens — the public, no-secret mint
 * endpoint consumed by the Compose Multiplatform desktop/Android/iOS clients.
 *
 * The endpoint MUST accept {platform, appVersion, installId}, return
 * {token, expiresInSec}, and reject malformed payloads with 422. The native
 * data layer's DeviceTokenInterceptor depends on this exact contract.
 *
 * @group http
 */
class DeviceTokenResourceTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->catchExceptions(true);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if ($metadata !== []) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        // .env.test.dist sets DEVICE_MEMBER_ID=1 — create a Member with id=1
        // so ApiAccessTokenHandler can resolve a UserBadge from the minted token.
        $member = new Member();
        $member->apiKey = 'device-member-key';
        $member->setEnabled(true);
        $member->setUsername('device');
        $em->persist($member);
        $em->flush();
    }

    public function test_valid_payload_returns_token_and_expiry(): void
    {
        $this->client->request(
            'POST',
            '/api/device-tokens',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'platform'   => 'desktop',
                'appVersion' => '22',
                'installId'  => 'probe-0001',
            ], JSON_THROW_ON_ERROR),
        );

        $response = $this->client->getResponse();
        self::assertSame(201, $response->getStatusCode(), $response->getContent() ?: 'no body');

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $body['token']);
        self::assertSame(900, $body['expiresInSec']);
    }

    public function test_each_call_returns_a_distinct_token(): void
    {
        $payload = json_encode([
            'platform'   => 'desktop',
            'appVersion' => '22',
            'installId'  => 'probe-0002',
        ], JSON_THROW_ON_ERROR);

        $tokens = [];
        foreach (range(1, 3) as $_) {
            $this->client->request(
                'POST',
                '/api/device-tokens',
                server: ['CONTENT_TYPE' => 'application/json'],
                content: $payload,
            );
            self::assertSame(201, $this->client->getResponse()->getStatusCode());
            $body = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $tokens[] = $body['token'];
        }

        self::assertCount(3, array_unique($tokens));
    }

    public function test_missing_install_id_returns_422(): void
    {
        $this->client->request(
            'POST',
            '/api/device-tokens',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['platform' => 'desktop', 'appVersion' => '22'], JSON_THROW_ON_ERROR),
        );

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function test_garbage_install_id_returns_422(): void
    {
        $this->client->request(
            'POST',
            '/api/device-tokens',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'platform'   => 'desktop',
                'appVersion' => '22',
                'installId'  => '!! not safe !!',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertSame(422, $this->client->getResponse()->getStatusCode());
    }

    public function test_no_authorization_header_required(): void
    {
        $this->client->request(
            'POST',
            '/api/device-tokens',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'platform'   => 'desktop',
                'appVersion' => '22',
                'installId'  => 'probe-0003',
            ], JSON_THROW_ON_ERROR),
        );

        // 201 confirms PUBLIC_ACCESS allowed the call through without any
        // Basic/Bearer header — that's the contract the native app relies on.
        self::assertSame(201, $this->client->getResponse()->getStatusCode());
    }
}

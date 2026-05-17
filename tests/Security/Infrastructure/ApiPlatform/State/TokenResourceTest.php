<?php
declare(strict_types=1);

namespace App\Tests\Security\Infrastructure\ApiPlatform\State;

use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group http
 */
class TokenResourceTest extends WebTestCase
{
    private const SECRET = 'dummy-test-secret';

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

        $member = new Member();
        $member->apiKey = self::SECRET;
        $member->setEnabled(true);
        $member->setUsername('test-user');
        $em->persist($member);
        $em->flush();
    }

    public function test_basic_auth_with_valid_secret_returns_bearer(): void
    {
        $this->client->request(
            'POST',
            '/api/token',
            server: ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(':' . self::SECRET)],
        );

        $response = $this->client->getResponse();

        self::assertSame(201, $response->getStatusCode(), $response->getContent() ?: 'no body');

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Bearer', $body['token_type']);
        self::assertSame(900, $body['expires_in']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $body['access_token']);
    }

    public function test_missing_authorization_header_returns_401(): void
    {
        $this->client->request('POST', '/api/token');
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function test_invalid_secret_returns_401(): void
    {
        $this->client->request(
            'POST',
            '/api/token',
            server: ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(':wrong-secret')],
        );
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function test_malformed_basic_header_returns_401(): void
    {
        $this->client->request(
            'POST',
            '/api/token',
            server: ['HTTP_AUTHORIZATION' => 'Basic !!!!'],
        );
        self::assertSame(401, $this->client->getResponse()->getStatusCode());
    }
}

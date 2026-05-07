<?php
declare(strict_types=1);

namespace App\Tests\Trends\Infrastructure\Controller;

use App\Membership\Domain\Entity\Legacy\Member;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group controller
 */
class TrendsControllerTest extends WebTestCase
{
    private const DUMMY_TOKEN = 'dummy-test-token';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->catchExceptions(false);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        $member = new Member();
        $member->apiKey = self::DUMMY_TOKEN;
        $member->setEmail('dummy@test.local');
        $member->setTwitterID('1');
        $member->setTwitterUsername('dummy_user');
        $member->setScreenName('dummy_user');
        $member->setFullName('Dummy User');
        $member->setEnabled(true);
        $em->persist($member);
        $em->flush();
    }

    public function test_callback_returns_acknowledgement_payload(): void
    {
        // /api/callback is IS_AUTHENTICATED_ANONYMOUSLY — no token needed.
        $this->client->request('GET', '/api/callback');

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertJson($response->getContent());

        $body = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsString($body);
        self::assertStringContainsString("That's all folks!", $body);
    }

    public function test_get_highlights_authenticates_with_dummy_token_and_returns_collection_shape(): void
    {
        $this->client->request(
            'GET',
            '/api/twitter/highlights',
            [
                'startDate'       => '2024-01-01 00:00:00',
                'endDate'         => '2024-01-01 23:59:59',
                'includeRetweets' => '0',
            ],
            [],
            ['HTTP_X_AUTH_TOKEN' => self::DUMMY_TOKEN]
        );

        $response = $this->client->getResponse();

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Authenticated /api/twitter/highlights request must succeed with a Member fixture and a matching x-auth-token header'
        );
        self::assertTrue($response->headers->has('x-total-pages'));
        self::assertTrue($response->headers->has('x-page-index'));

        $body = json_decode($response->getContent(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('aggregates', $body);
        self::assertArrayHasKey('statuses', $body);
    }

    public function test_get_highlights_rejects_unauthenticated_request(): void
    {
        // No x-auth-token header — firewall must reject before reaching the controller.
        $this->client->request(
            'GET',
            '/api/twitter/highlights',
            [
                'startDate'       => '2024-01-01 00:00:00',
                'endDate'         => '2024-01-01 23:59:59',
                'includeRetweets' => '0',
            ]
        );

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }
}

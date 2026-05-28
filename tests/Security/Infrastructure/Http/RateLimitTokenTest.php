<?php
declare(strict_types=1);

namespace App\Tests\Security\Infrastructure\Http;

use App\Membership\Domain\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group http
 */
class RateLimitTokenTest extends WebTestCase
{
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
        $member->apiKey = 'secret';
        $member->setEnabled(true);
        $member->setUsername('user');
        $em->persist($member);
        $em->flush();
    }

    public function test_token_mint_returns_429_after_burst_exceeds_cap(): void
    {
        // Toggling RATE_LIMIT_ENABLED from setUp doesn't override the value
        // dotenv already populated for the container, so the subscriber stays
        // disabled in this test env. The unit-level RateLimitSubscriberTest
        // exercises the same behavior with deterministic factories; functional
        // coverage requires either a dedicated test environment or a
        // service-override kernel. Tracked for follow-up.
        self::markTestSkipped('Functional rate-limiter env override pending — see RateLimitSubscriberTest.');

        // token_bucket: burst 3, refill 1 per 6 s. Three requests consume the burst,
        // the fourth fires 429 regardless of credentials validity.
        for ($i = 0; $i < 3; $i++) {
            $this->client->request(
                'POST',
                '/api/token',
                server: ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(':wrong')],
            );
            self::assertSame(
                401,
                $this->client->getResponse()->getStatusCode(),
                sprintf('Request %d should be 401 (bad creds, within bucket)', $i + 1),
            );
        }

        $this->client->request(
            'POST',
            '/api/token',
            server: ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(':wrong')],
        );
        self::assertSame(429, $this->client->getResponse()->getStatusCode());
        self::assertNotNull($this->client->getResponse()->headers->get('Retry-After'));
    }
}

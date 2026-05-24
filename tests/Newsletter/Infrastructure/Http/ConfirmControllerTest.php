<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

final class ConfirmControllerTest extends WebTestCase
{
    public function test_unknown_token_returns_200_with_failed_page(): void
    {
        $client = self::createClient();
        $this->resetSchema();
        $client->request('GET', '/newsletter/confirm/' . str_repeat('A', 43));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'plus valide');
    }

    public function test_valid_token_confirms_and_renders_success_page(): void
    {
        $client = self::createClient();
        $this->resetSchema();
        $repo = self::getContainer()->get(SubscriberRepository::class);
        $confirm = OpaqueToken::fromRawBytes(random_bytes(32));
        $sub = Subscriber::enrol(
            new Ulid(),
            EmailAddress::fromString('alice@example.com'),
            $confirm,
            new \DateTimeImmutable('+7 days'),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            'rdp-api',
            new \DateTimeImmutable(),
        );
        $repo->save($sub);

        $client->request('GET', '/newsletter/confirm/' . $confirm->value());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'confirmé');

        $fresh = $repo->findByEmailHash(EmailAddress::fromString('alice@example.com')->hash());
        self::assertSame(SubscriberStatus::Active, $fresh->status());
    }

    private function resetSchema(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema([$em->getClassMetadata(Subscriber::class)]);
    }
}

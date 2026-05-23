<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Infrastructure\Doctrine\BootEncryptedStringType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

final class UnsubscribeControllerTest extends WebTestCase
{
    public function test_get_renders_confirm_page_with_csrf_form(): void
    {
        $client = self::createClient();
        $unsub = $this->seed();
        $client->request('GET', '/newsletter/unsubscribe/' . $unsub);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form input[name="_csrf_token"]');
    }

    public function test_post_with_one_click_body_unsubscribes(): void
    {
        $client = self::createClient();
        $unsub = $this->seed();
        $client->request(
            'POST',
            '/newsletter/unsubscribe/' . $unsub,
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            'List-Unsubscribe=One-Click'
        );
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'désabonné');
    }

    private function seed(): string
    {
        // Force BootEncryptedStringType instantiation so EncryptedStringType::injectKey()
        // is called before any Doctrine flush touches the encrypted email column.
        self::getContainer()->get(BootEncryptedStringType::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema([$em->getClassMetadata(Subscriber::class)]);
        $repo = self::getContainer()->get(SubscriberRepository::class);
        $confirm = OpaqueToken::fromRawBytes(random_bytes(32));
        $unsub = OpaqueToken::fromRawBytes(random_bytes(32));
        $sub = Subscriber::enrol(
            new Ulid(),
            EmailAddress::fromString('alice@example.com'),
            $confirm,
            new \DateTimeImmutable('+7 days'),
            $unsub,
            'rdp-api',
            new \DateTimeImmutable(),
        );
        $sub->confirm($confirm, new \DateTimeImmutable());
        $repo->save($sub);
        return $unsub->value();
    }
}

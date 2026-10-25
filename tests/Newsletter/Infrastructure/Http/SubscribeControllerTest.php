<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use App\Newsletter\Infrastructure\Doctrine\BootEncryptedStringType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Uid\Ulid;

final class SubscribeControllerTest extends WebTestCase
{
    public function test_valid_email_creates_pending_row_and_sends_confirmation(): void
    {
        $client = $this->client();
        $this->subscribe($client, 'alice@example.com');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'boîte de réception');
        self::assertEmailCount(1);

        $sub = $this->find('alice@example.com');
        self::assertNotNull($sub);
        self::assertSame(SubscriberStatus::Pending, $sub->status());
        self::assertSame('public-form', $sub->enrolledBy());
    }

    public function test_filled_honeypot_is_silently_ignored(): void
    {
        $client = $this->client();
        $this->subscribe($client, 'bot@example.com', website: 'https://spam.example');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'boîte de réception');
        self::assertEmailCount(0);
        self::assertNull($this->find('bot@example.com'));
    }

    public function test_invalid_email_is_rejected(): void
    {
        $client = $this->client();
        $this->subscribe($client, 'not-an-email');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'adresse');
        self::assertEmailCount(0);
    }

    public function test_active_subscriber_gets_same_page_and_no_email(): void
    {
        $client = $this->client();
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
        $sub->confirm($confirm, new \DateTimeImmutable());
        $repo->save($sub);

        $this->subscribe($client, 'alice@example.com');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'boîte de réception');
        self::assertEmailCount(0);
    }

    public function test_confirmation_emails_per_address_are_capped(): void
    {
        $client = $this->client();
        // The array cache pool behind the real limiter is reset between
        // requests, so swap in a storage that survives them.
        self::getContainer()->set('limiter.newsletter_subscribe_email', new RateLimiterFactory(
            ['id' => 'newsletter_subscribe_email', 'policy' => 'fixed_window', 'limit' => 3, 'interval' => '1 day'],
            new InMemoryStorage(),
        ));

        for ($i = 0; $i < 3; $i++) {
            $this->subscribe($client, 'alice@example.com');
            self::assertEmailCount(1);
        }

        $this->subscribe($client, 'alice@example.com');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'boîte de réception');
        self::assertEmailCount(0);
    }

    public function test_the_edition_field_picks_the_newsletter(): void
    {
        $client = $this->client();
        $this->subscribe($client, 'alice@example.com', edition: 'coop-medias');

        self::assertResponseIsSuccessful();
        self::assertEmailCount(1);
        self::assertEmailTextBodyContains(self::getMailerMessage(), "la lettre d'information de Coop Médias");
        self::assertNull($this->find('alice@example.com'));
        self::assertSame(Edition::CoopMedias, $this->find('alice@example.com', Edition::CoopMedias)?->edition());
    }

    public function test_both_letters_take_one_confirmation_mail(): void
    {
        $client = $this->client();
        $this->subscribe($client, 'alice@example.com', edition: 'both');

        self::assertResponseIsSuccessful();
        self::assertEmailCount(1);
        self::assertSame(Edition::FrenchMedia, $this->find('alice@example.com')?->edition());
        self::assertSame(Edition::CoopMedias, $this->find('alice@example.com', Edition::CoopMedias)?->edition());
    }

    public function test_unknown_edition_is_rejected(): void
    {
        $client = $this->client();
        $this->subscribe($client, 'alice@example.com', edition: '../etc');

        self::assertResponseStatusCodeSame(422);
        self::assertEmailCount(0);
        self::assertNull($this->find('alice@example.com'));
    }

    public function test_get_redirects_to_the_signup_page(): void
    {
        $client = $this->client();
        $client->request('GET', '/newsletter/subscribe');

        self::assertResponseRedirects('https://revue-de-presse.org/lettre-quotidienne');
    }

    private function client(): KernelBrowser
    {
        $client = self::createClient();
        // Keep the in-memory database and array-backed rate limiters alive
        // across requests within one test.
        $client->disableReboot();
        self::getContainer()->get(BootEncryptedStringType::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema([$em->getClassMetadata(Subscriber::class)]);
        return $client;
    }

    private function subscribe(KernelBrowser $client, string $email, string $website = '', ?string $edition = null): void
    {
        $fields = ['email' => $email, 'website' => $website];
        if ($edition !== null) {
            $fields['edition'] = $edition;
        }
        $client->request('POST', '/newsletter/subscribe', $fields);
    }

    private function find(string $email, Edition $edition = Edition::FrenchMedia): ?Subscriber
    {
        $repo = self::getContainer()->get(SubscriberRepository::class);
        return $repo->findByEmailHash(EmailAddress::fromString($email)->hash(), $edition);
    }
}

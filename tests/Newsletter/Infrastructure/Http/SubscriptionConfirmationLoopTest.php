<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\Service\DailyHighlightsSource;
use App\Newsletter\Domain\Service\HighlightView;
use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\SubscriberStatus;
use App\Newsletter\Infrastructure\Doctrine\BootEncryptedStringType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Email;

/**
 * Whole double opt-in loop, as a visitor lives it:
 * sign up → "confirm your address" email → click its link → receive the daily letter.
 * Nothing is sent to an address that has not been confirmed.
 */
final class SubscriptionConfirmationLoopTest extends WebTestCase
{
    public function test_only_confirmed_addresses_receive_the_daily_letter(): void
    {
        $client = $this->client();

        // 1. Two visitors sign up from the website form.
        $aliceConfirmUrl = $this->signUpAndReadConfirmationLink($client, 'alice@example.com');
        $this->signUpAndReadConfirmationLink($client, 'bob@example.com');

        self::assertSame(SubscriberStatus::Pending, $this->find('alice@example.com')->status());
        self::assertSame(SubscriberStatus::Pending, $this->find('bob@example.com')->status());

        // 2. Before anyone confirms, the daily send reaches nobody.
        self::assertStringContainsString('0 sent, 0 failed, 0 considered', $this->sendDaily($client));

        // 3. Alice clicks the link in her email; Bob never does.
        $client->request('GET', parse_url($aliceConfirmUrl, PHP_URL_PATH));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'adresse e-mail est confirmée');
        self::assertSame(SubscriberStatus::Active, $this->find('alice@example.com')->status());
        self::assertSame(SubscriberStatus::Pending, $this->find('bob@example.com')->status());

        // Clicking the same link again is harmless.
        $client->request('GET', parse_url($aliceConfirmUrl, PHP_URL_PATH));
        self::assertResponseIsSuccessful();
        self::assertSame(SubscriberStatus::Active, $this->find('alice@example.com')->status());

        // 4. The daily send now reaches Alice only.
        self::assertStringContainsString('1 sent, 0 failed, 1 considered', $this->sendDaily($client));
    }

    public function test_each_edition_is_confirmed_and_sent_on_its_own(): void
    {
        $client = $this->client();

        // Alice signs up to both newsletters, and only confirms Coop Médias.
        $this->signUpAndReadConfirmationLink($client, 'alice@example.com');
        $coopConfirmUrl = $this->signUpAndReadConfirmationLink($client, 'alice@example.com', Edition::CoopMedias);

        $client->request('GET', parse_url($coopConfirmUrl, PHP_URL_PATH));
        self::assertResponseIsSuccessful();
        self::assertSame(SubscriberStatus::Pending, $this->find('alice@example.com')->status());
        self::assertSame(SubscriberStatus::Active, $this->find('alice@example.com', Edition::CoopMedias)->status());

        $display = $this->sendDaily($client);
        self::assertStringContainsString('fr: 0 sent, 0 failed, 0 considered', $display);
        self::assertStringContainsString('coop-medias: 1 sent, 0 failed, 1 considered', $display);
    }

    public function test_tampered_confirmation_link_does_not_activate(): void
    {
        $client = $this->client();
        $url = $this->signUpAndReadConfirmationLink($client, 'alice@example.com');

        $path = (string) parse_url($url, PHP_URL_PATH);
        $tampered = substr($path, 0, -1) . (str_ends_with($path, 'A') ? 'B' : 'A');
        $client->request('GET', $tampered);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'plus valide');
        self::assertSame(SubscriberStatus::Pending, $this->find('alice@example.com')->status());
    }

    private function signUpAndReadConfirmationLink(KernelBrowser $client, string $address, Edition $edition = Edition::FrenchMedia): string
    {
        $client->request('POST', '/newsletter/subscribe', ['email' => $address, 'website' => '', 'edition' => $edition->value]);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Confirmez votre adresse e-mail');
        self::assertEmailCount(1);

        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame($address, $email->getTo()[0]->getAddress());
        self::assertSame('Confirmez votre adresse e-mail — Revue de presse', $email->getSubject());
        self::assertEmailHtmlBodyContains($email, 'Confirmer mon adresse e-mail');

        self::assertSame(1, preg_match('#https?://\S+/newsletter/confirm/[A-Za-z0-9_-]{43}#', (string) $email->getTextBody(), $m));
        self::assertStringContainsString($m[0], (string) $email->getHtmlBody(), 'HTML and text parts carry the same link');

        return $m[0];
    }

    private function sendDaily(KernelBrowser $client): string
    {
        $tester = new CommandTester((new Application($client->getKernel()))->find('newsletter:send-daily'));
        $exit = $tester->execute(['--date' => '2026-05-22']);
        $display = $tester->getDisplay();
        self::assertSame(0, $exit, $display);

        return preg_replace('/\s+/', ' ', $display) ?? $display;
    }

    private function client(): KernelBrowser
    {
        $client = self::createClient();
        $client->disableReboot();
        self::getContainer()->get(BootEncryptedStringType::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $tool->createSchema([$em->getClassMetadata(Subscriber::class)]);

        self::getContainer()->set(DailyHighlightsSource::class, new class implements DailyHighlightsSource {
            public function fetchTop10(\DateTimeImmutable $date, Edition $edition = Edition::FrenchMedia): array
            {
                return [new HighlightView('01', 'lemonde.fr', null, 'Une brève', '22 mai 2026', 1, 2, 'https://bsky.app/profile/lemonde.fr/post/1')];
            }
        });

        return $client;
    }

    private function find(string $address, Edition $edition = Edition::FrenchMedia): Subscriber
    {
        $sub = self::getContainer()->get(SubscriberRepository::class)
            ->findByEmailHash(EmailAddress::fromString($address)->hash(), $edition);
        self::assertNotNull($sub);

        return $sub;
    }
}

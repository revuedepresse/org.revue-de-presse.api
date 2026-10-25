<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Service;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Service\DailyReportRenderer;
use App\Newsletter\Domain\Service\HighlightView;
use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Uid\Ulid;

final class DailyReportRendererTest extends KernelTestCase
{
    public function test_email_has_list_unsubscribe_headers_and_two_parts(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(DailyReportRenderer::class);

        $sub = Subscriber::enrol(
            new Ulid(),
            EmailAddress::fromString('alice@example.com'),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            new \DateTimeImmutable('+7 days'),
            OpaqueToken::fromRawBytes(random_bytes(32)),
            'rdp-api',
            new \DateTimeImmutable(),
        );

        $highlights = [new HighlightView('01', 'lemonde', null, 'A headline', '23 mai 2026', 124, 489, 'https://bsky.app/...')];

        $email = $renderer->render($sub, $highlights, new \DateTimeImmutable('2026-05-23'));
        $headers = $email->getHeaders();

        self::assertNotNull($headers->get('List-Unsubscribe'));
        self::assertSame('List-Unsubscribe=One-Click', $headers->get('List-Unsubscribe-Post')->getBodyAsString());
        self::assertSame('auto-generated', $headers->get('Auto-Submitted')->getBodyAsString());
    }

    public function test_each_edition_names_its_publishers_in_the_subject_and_the_body(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(DailyReportRenderer::class);
        $highlights = [new HighlightView('01', 'blast-info.fr', null, 'A headline', '23 mai 2026', 1, 2, 'https://bsky.app/...')];

        $render = static function (Edition $edition) use ($renderer, $highlights) {
            $sub = Subscriber::enrol(
                new Ulid(),
                EmailAddress::fromString('alice@example.com'),
                OpaqueToken::fromRawBytes(random_bytes(32)),
                new \DateTimeImmutable('+7 days'),
                OpaqueToken::fromRawBytes(random_bytes(32)),
                'rdp-api',
                new \DateTimeImmutable(),
                $edition,
            );

            $email = $renderer->render($sub, $highlights, new \DateTimeImmutable('2026-05-23'));
            self::getContainer()->get(BodyRendererInterface::class)->render($email);

            return $email;
        };

        $french = $render(Edition::FrenchMedia);
        self::assertSame('Revue de presse — 23 mai 2026', $french->getSubject());
        self::assertStringContainsString('des médias français', (string) $french->getTextBody());

        $coop = $render(Edition::CoopMedias);
        self::assertSame('Revue de presse Coop Médias — 23 mai 2026', $coop->getSubject());
        self::assertStringContainsString('des médias de Coop Médias', (string) $coop->getTextBody());
        self::assertStringContainsString("la lettre d'information de Coop Médias", (string) $coop->getTextBody());
        self::assertStringContainsString('des médias de Coop Médias', (string) $coop->getHtmlBody());

        $combined = $render(Edition::FrenchAndCoopMedias);
        self::assertSame('Revue de presse, médias français et Coop Médias — 23 mai 2026', $combined->getSubject());
        self::assertStringContainsString('des médias français et de Coop Médias', (string) $combined->getTextBody());
        self::assertStringContainsString("la lettre d'information des médias français et de Coop Médias", (string) $combined->getTextBody());
    }
}

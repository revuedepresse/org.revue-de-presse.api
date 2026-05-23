<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Service;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Domain\Service\DailyReportRenderer;
use App\Newsletter\Domain\Service\HighlightView;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
}

<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Config;

use App\Newsletter\Infrastructure\Config\InvalidNewsletterConfigException;
use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use PHPUnit\Framework\TestCase;

final class NewsletterConfigTest extends TestCase
{
    public function test_happy_path(): void
    {
        $cfg = new NewsletterConfig(
            timezone: 'Europe/Paris',
            confirmTtlHours: 168,
            fromEmail: 'noreply@revue-de-presse.org',
            fromName: 'Revue de Presse',
            baseUrl: 'https://api.revue-de-presse.org',
            designTokensPath: '/tmp/tokens.json',
            encryptionKey: base64_encode(str_repeat("\x00", 32)),
            encryptionKeyNext: '',
        );
        self::assertSame('Europe/Paris', $cfg->timezone);
        self::assertSame(168, $cfg->confirmTtlHours);
    }

    public function test_rejects_missing_encryption_key(): void
    {
        $this->expectException(InvalidNewsletterConfigException::class);
        new NewsletterConfig('Europe/Paris', 168, 'a@b.com', 'X', 'https://x', '', '', '');
    }

    public function test_rejects_malformed_base_url(): void
    {
        $this->expectException(InvalidNewsletterConfigException::class);
        new NewsletterConfig('Europe/Paris', 168, 'a@b.com', 'X', 'not-a-url', '', base64_encode(str_repeat("\x00", 32)), '');
    }
}

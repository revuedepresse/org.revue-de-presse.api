<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Config;

final class NewsletterConfig
{
    public function __construct(
        public readonly string $timezone,
        public readonly int $confirmTtlHours,
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly string $baseUrl,
        public readonly string $designTokensPath,
        public readonly string $encryptionKey,
        public readonly string $encryptionKeyNext,
    ) {
        if ($encryptionKey === '') {
            throw new InvalidNewsletterConfigException('NEWSLETTER_ENCRYPTION_KEY is required');
        }
        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidNewsletterConfigException('NEWSLETTER_BASE_URL is malformed');
        }
        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidNewsletterConfigException('NEWSLETTER_FROM_EMAIL is malformed');
        }
        if ($confirmTtlHours < 1) {
            throw new InvalidNewsletterConfigException('NEWSLETTER_CONFIRM_TTL_HOURS must be >= 1');
        }
    }

    public function confirmUrl(string $token): string
    {
        return rtrim($this->baseUrl, '/') . '/newsletter/confirm/' . $token;
    }

    public function unsubscribeUrl(string $token): string
    {
        return rtrim($this->baseUrl, '/') . '/newsletter/unsubscribe/' . $token;
    }
}

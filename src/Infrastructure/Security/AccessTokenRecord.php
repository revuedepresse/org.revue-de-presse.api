<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

final readonly class AccessTokenRecord
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $issuedAt,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }
}

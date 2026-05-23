<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\ValueObject;

use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\InvalidEmailAddress;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    public function test_normalises_to_lowercase_and_trims(): void
    {
        $email = EmailAddress::fromString('  Alice@Example.COM  ');
        self::assertSame('alice@example.com', $email->value());
    }

    public function test_hash_is_stable_sha256_of_normalised(): void
    {
        $email = EmailAddress::fromString('alice@example.com');
        self::assertSame(hash('sha256', 'alice@example.com'), $email->hash());
    }

    public function test_rejects_malformed_input(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        EmailAddress::fromString('not-an-email');
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        EmailAddress::fromString('   ');
    }

    public function test_to_string_redacts_local_part(): void
    {
        $email = EmailAddress::fromString('alice@example.com');
        self::assertSame('a***@example.com', (string) $email);
    }

    public function test_unmask_returns_plain_value(): void
    {
        $email = EmailAddress::fromString('alice@example.com');
        self::assertSame('alice@example.com', $email->unmask());
    }
}

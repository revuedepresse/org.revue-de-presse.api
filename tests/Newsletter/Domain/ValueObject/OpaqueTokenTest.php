<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\ValueObject;

use App\Newsletter\Domain\ValueObject\InvalidOpaqueToken;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use PHPUnit\Framework\TestCase;

final class OpaqueTokenTest extends TestCase
{
    public function test_from_raw_base64url_encodes_to_43_chars(): void
    {
        $bytes = str_repeat("\x00", 32);
        $token = OpaqueToken::fromRawBytes($bytes);
        self::assertSame(43, strlen($token->value()));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token->value());
    }

    public function test_from_string_round_trips(): void
    {
        $original = OpaqueToken::fromRawBytes(random_bytes(32));
        $rehydrated = OpaqueToken::fromString($original->value());
        self::assertTrue($original->equals($rehydrated));
    }

    public function test_equals_is_constant_time(): void
    {
        $a = OpaqueToken::fromRawBytes(random_bytes(32));
        $b = OpaqueToken::fromString($a->value());
        self::assertTrue($a->equals($b));
    }

    public function test_equals_rejects_different_token(): void
    {
        $a = OpaqueToken::fromRawBytes(random_bytes(32));
        $b = OpaqueToken::fromRawBytes(random_bytes(32));
        self::assertFalse($a->equals($b));
    }

    public function test_from_string_rejects_short_input(): void
    {
        $this->expectException(InvalidOpaqueToken::class);
        OpaqueToken::fromString('too-short');
    }

    public function test_from_raw_bytes_requires_exactly_32_bytes(): void
    {
        $this->expectException(InvalidOpaqueToken::class);
        OpaqueToken::fromRawBytes(str_repeat("\x00", 16));
    }
}

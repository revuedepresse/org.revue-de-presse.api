<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Doctrine\Type;

use App\Newsletter\Infrastructure\Crypto\StaticEncryptionKey;
use App\Newsletter\Infrastructure\Doctrine\Type\EncryptedStringType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\TestCase;

final class EncryptedStringTypeTest extends TestCase
{
    private function makeType(): EncryptedStringType
    {
        $key = new StaticEncryptionKey(base64_encode(str_repeat("\x00", 32)));
        $type = new EncryptedStringType();
        EncryptedStringType::injectKey($key);
        return $type;
    }

    public function test_round_trips_a_value(): void
    {
        $type = $this->makeType();
        $platform = new PostgreSQLPlatform();
        $cipher = $type->convertToDatabaseValue('alice@example.com', $platform);
        self::assertNotSame('alice@example.com', $cipher);
        self::assertSame('alice@example.com', $type->convertToPHPValue($cipher, $platform));
    }

    public function test_each_encrypt_uses_fresh_nonce(): void
    {
        $type = $this->makeType();
        $platform = new PostgreSQLPlatform();
        $a = $type->convertToDatabaseValue('alice@example.com', $platform);
        $b = $type->convertToDatabaseValue('alice@example.com', $platform);
        self::assertNotSame($a, $b, 'nonce must vary per row');
    }

    public function test_null_round_trips_as_null(): void
    {
        $type = $this->makeType();
        $platform = new PostgreSQLPlatform();
        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertNull($type->convertToPHPValue(null, $platform));
    }
}

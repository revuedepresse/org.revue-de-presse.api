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

    public function test_uses_next_key_for_encryption_when_set(): void
    {
        // Simulate rotation: PRIMARY = old, NEXT = new
        $oldKeyBase64 = base64_encode(str_repeat("\x01", 32));
        $newKeyBase64 = base64_encode(str_repeat("\x02", 32));

        $type = new EncryptedStringType();
        EncryptedStringType::injectKey(new StaticEncryptionKey($oldKeyBase64, $newKeyBase64));
        $platform = new PostgreSQLPlatform();

        $cipher = $type->convertToDatabaseValue('rotated@example.com', $platform);

        // Switch to only the NEW key as primary (simulating post-rotation env)
        EncryptedStringType::injectKey(new StaticEncryptionKey($newKeyBase64));
        self::assertSame('rotated@example.com', $type->convertToPHPValue($cipher, $platform));

        // And the OLD key alone CANNOT decrypt it
        EncryptedStringType::injectKey(new StaticEncryptionKey($oldKeyBase64));
        $this->expectException(\RuntimeException::class);
        $type->convertToPHPValue($cipher, $platform);
    }
}

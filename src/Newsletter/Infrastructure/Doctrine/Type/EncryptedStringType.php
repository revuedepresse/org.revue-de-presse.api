<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Doctrine\Type;

use App\Newsletter\Infrastructure\Crypto\StaticEncryptionKey;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class EncryptedStringType extends Type
{
    public const NAME = 'newsletter_encrypted_string';

    private static ?StaticEncryptionKey $key = null;

    public static function injectKey(StaticEncryptionKey $key): void
    {
        self::$key = $key;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBlobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        if (self::$key === null) {
            throw new \LogicException('EncryptedStringType not initialised; call injectKey() at boot.');
        }
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $key = self::$key->next() ?? self::$key->primary();
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt((string) $value, '', $nonce, $key);
        return $nonce . $cipher;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        if (self::$key === null) {
            throw new \LogicException('EncryptedStringType not initialised; call injectKey() at boot.');
        }
        $raw = is_resource($value) ? stream_get_contents($value) : (string) $value;
        $nonce = substr($raw, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, '', $nonce, self::$key->primary());
        if ($plain === false && self::$key->next() !== null) {
            $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, '', $nonce, self::$key->next());
        }
        if ($plain === false) {
            throw new \RuntimeException('newsletter_encrypted_string: decryption failed');
        }
        return $plain;
    }
}

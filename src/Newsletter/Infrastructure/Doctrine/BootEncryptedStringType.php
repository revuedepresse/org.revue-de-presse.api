<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Doctrine;

use App\Newsletter\Infrastructure\Crypto\StaticEncryptionKey;
use App\Newsletter\Infrastructure\Doctrine\Type\EncryptedStringType;

final class BootEncryptedStringType
{
    public function __construct(private readonly StaticEncryptionKey $key)
    {
        if (!\sodium_crypto_aead_aes256gcm_is_available()) {
            throw new \RuntimeException(
                'AES-256-GCM not available on this host (libsodium reports AES-NI missing). ' .
                'Newsletter encryption requires AES-NI. Use a CPU with AES-NI or switch to XChaCha20-Poly1305.'
            );
        }
        EncryptedStringType::injectKey($this->key);
    }

    public function __invoke(): void
    {
        // No-op: construction already injected the key. The tagged listener
        // is here only to force the service to be instantiated at boot.
    }
}

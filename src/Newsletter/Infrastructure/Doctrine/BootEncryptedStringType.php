<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Doctrine;

use App\Newsletter\Infrastructure\Crypto\StaticEncryptionKey;
use App\Newsletter\Infrastructure\Doctrine\Type\EncryptedStringType;

final class BootEncryptedStringType
{
    public function __construct(private readonly StaticEncryptionKey $key)
    {
        EncryptedStringType::injectKey($this->key);
    }

    public function __invoke(): void
    {
        // No-op: construction already injected the key. The tagged listener
        // is here only to force the service to be instantiated at boot.
    }
}

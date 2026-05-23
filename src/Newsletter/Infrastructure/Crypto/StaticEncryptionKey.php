<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Crypto;

final class StaticEncryptionKey
{
    private readonly string $primaryKey;
    private readonly ?string $nextKey;

    public function __construct(string $base64PrimaryKey, ?string $base64NextKey = null)
    {
        $this->primaryKey = KeyDerivation::derive32ByteKey($base64PrimaryKey, 'newsletter-email-v1');
        $this->nextKey = $base64NextKey === null || $base64NextKey === ''
            ? null
            : KeyDerivation::derive32ByteKey($base64NextKey, 'newsletter-email-v1');
    }

    public function primary(): string { return $this->primaryKey; }
    public function next(): ?string   { return $this->nextKey; }
}

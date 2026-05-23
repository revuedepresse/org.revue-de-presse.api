<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\ValueObject;

final class OpaqueToken
{
    private const ENCODED_LENGTH = 43; // base64url(32 bytes) without padding

    private function __construct(private readonly string $encoded)
    {
    }

    public static function fromRawBytes(string $bytes): self
    {
        if (strlen($bytes) !== 32) {
            throw new InvalidOpaqueToken('opaque token must derive from exactly 32 bytes');
        }

        return new self(rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='));
    }

    public static function fromString(string $encoded): self
    {
        if (strlen($encoded) !== self::ENCODED_LENGTH
            || preg_match('/^[A-Za-z0-9_-]+$/', $encoded) !== 1) {
            throw new InvalidOpaqueToken('opaque token must be 43 base64url characters');
        }

        return new self($encoded);
    }

    public function value(): string
    {
        return $this->encoded;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->encoded, $other->encoded);
    }
}

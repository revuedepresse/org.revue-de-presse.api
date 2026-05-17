<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use PHPUnit\Framework\TestCase;

class InMemoryAccessTokenStoreTest extends TestCase
{
    public function test_put_then_resolve_returns_record_for_member(): void
    {
        $store = new InMemoryAccessTokenStore();
        $store->put('plaintext-abc', '42', 900);

        $record = $store->resolve('plaintext-abc');

        self::assertNotNull($record);
        self::assertSame('42', $record->memberId);
    }

    public function test_resolve_returns_null_for_unknown_token(): void
    {
        $store = new InMemoryAccessTokenStore();

        self::assertNull($store->resolve('never-stored'));
    }

    public function test_resolve_returns_null_after_revoke(): void
    {
        $store = new InMemoryAccessTokenStore();
        $store->put('plaintext-xyz', '42', 900);
        $store->revoke('plaintext-xyz');

        self::assertNull($store->resolve('plaintext-xyz'));
    }

    public function test_resolve_returns_null_for_expired_record(): void
    {
        $store = new InMemoryAccessTokenStore();
        $store->put('plaintext-expired', '42', ttlSeconds: -1);

        self::assertNull($store->resolve('plaintext-expired'));
    }

    public function test_uses_sha256_keying_internally(): void
    {
        $store = new InMemoryAccessTokenStore();
        $store->put('plaintext-key-check', '42', 900);

        self::assertTrue($store->hasKey(hash('sha256', 'plaintext-key-check')));
        self::assertFalse($store->hasKey('plaintext-key-check'));
    }
}

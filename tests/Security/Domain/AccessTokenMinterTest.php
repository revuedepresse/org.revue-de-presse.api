<?php
declare(strict_types=1);

namespace App\Tests\Security\Domain;

use App\Security\Domain\AccessTokenMinter;
use PHPUnit\Framework\TestCase;

class AccessTokenMinterTest extends TestCase
{
    public function test_returns_64_hex_token(): void
    {
        $store = new InMemoryAccessTokenStore();
        $minter = new AccessTokenMinter($store, ttlSeconds: 900);

        $token = $minter->mint('42');

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function test_persists_token_in_store_with_ttl(): void
    {
        $store = new InMemoryAccessTokenStore();
        $minter = new AccessTokenMinter($store, ttlSeconds: 900);

        $token = $minter->mint('42');

        self::assertNotNull($store->resolve($token));
    }

    public function test_each_call_returns_a_distinct_token(): void
    {
        $store = new InMemoryAccessTokenStore();
        $minter = new AccessTokenMinter($store, ttlSeconds: 900);

        $tokens = [$minter->mint('42'), $minter->mint('42'), $minter->mint('42')];

        self::assertCount(3, array_unique($tokens));
    }
}

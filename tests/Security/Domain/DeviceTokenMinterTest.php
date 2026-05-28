<?php
declare(strict_types=1);

namespace App\Tests\Security\Domain;

use App\Security\Domain\DeviceTokenMinter;
use PHPUnit\Framework\TestCase;

class DeviceTokenMinterTest extends TestCase
{
    private const DEVICE_MEMBER_ID = '7';

    public function test_returns_64_hex_token_and_configured_ttl(): void
    {
        $store = new InMemoryAccessTokenStore();
        $minter = new DeviceTokenMinter($store, self::DEVICE_MEMBER_ID, ttlSeconds: 900);

        $dto = $minter->mint();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $dto->token);
        self::assertSame(900, $dto->expiresInSec);
    }

    public function test_persists_token_against_device_member_id(): void
    {
        $store = new InMemoryAccessTokenStore();
        $minter = new DeviceTokenMinter($store, self::DEVICE_MEMBER_ID, ttlSeconds: 900);

        $dto = $minter->mint();

        $record = $store->resolve($dto->token);
        self::assertNotNull($record);
        self::assertSame(self::DEVICE_MEMBER_ID, $record->memberId);
    }

    public function test_each_call_returns_a_distinct_token(): void
    {
        $store = new InMemoryAccessTokenStore();
        $minter = new DeviceTokenMinter($store, self::DEVICE_MEMBER_ID, ttlSeconds: 60);

        $tokens = [$minter->mint()->token, $minter->mint()->token, $minter->mint()->token];

        self::assertCount(3, array_unique($tokens));
    }
}

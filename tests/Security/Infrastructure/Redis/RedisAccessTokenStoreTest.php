<?php
declare(strict_types=1);

namespace App\Tests\Security\Infrastructure\Redis;

use App\Security\Infrastructure\Redis\RedisAccessTokenStore;
use PHPUnit\Framework\TestCase;

class RedisAccessTokenStoreTest extends TestCase
{
    public function test_put_calls_setex_on_sha256_key_with_member_id_payload(): void
    {
        $client = $this->makeStubClient();
        $store = new RedisAccessTokenStore($client);

        $store->put('plaintext-token', '42', 900);

        $expectedKey = 'auth:bearer:' . hash('sha256', 'plaintext-token');
        self::assertArrayHasKey($expectedKey, $client->writes);
        [$ttl, $payload] = $client->writes[$expectedKey];
        self::assertSame(900, $ttl);
        $decoded = json_decode($payload, true);
        self::assertSame('42', $decoded['m']);
        self::assertArrayHasKey('i', $decoded);
        self::assertArrayHasKey('e', $decoded);
    }

    public function test_resolve_returns_record_for_active_token(): void
    {
        $client = $this->makeStubClient();
        $store = new RedisAccessTokenStore($client);

        $store->put('plaintext-token', '42', 900);
        $record = $store->resolve('plaintext-token');

        self::assertNotNull($record);
        self::assertSame('42', $record->memberId);
    }

    public function test_resolve_returns_null_for_unknown_token(): void
    {
        $client = $this->makeStubClient();
        $store = new RedisAccessTokenStore($client);

        self::assertNull($store->resolve('never-stored'));
    }

    public function test_resolve_returns_null_when_record_e_in_past(): void
    {
        $client = $this->makeStubClient();
        $store = new RedisAccessTokenStore($client);

        $key = 'auth:bearer:' . hash('sha256', 'expired-token');
        $now = time();
        $client->writes[$key] = [900, json_encode(['m' => '42', 'i' => $now - 1000, 'e' => $now - 1])];

        self::assertNull($store->resolve('expired-token'));
    }

    public function test_revoke_deletes_sha256_keyed_entry(): void
    {
        $client = $this->makeStubClient();
        $store = new RedisAccessTokenStore($client);

        $store->put('plaintext-token', '42', 900);
        $store->revoke('plaintext-token');

        $key = 'auth:bearer:' . hash('sha256', 'plaintext-token');
        self::assertArrayNotHasKey($key, $client->writes);
    }

    private function makeStubClient(): object
    {
        return new class {
            public array $writes = [];
            public function setex(string $key, int $ttl, string $value): bool
            {
                $this->writes[$key] = [$ttl, $value];
                return true;
            }
            public function get(string $key): ?string
            {
                return $this->writes[$key][1] ?? null;
            }
            public function del(string $key): int
            {
                if (isset($this->writes[$key])) {
                    unset($this->writes[$key]);
                    return 1;
                }
                return 0;
            }
        };
    }
}

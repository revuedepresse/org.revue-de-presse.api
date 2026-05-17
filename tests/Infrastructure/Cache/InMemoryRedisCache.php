<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Cache;

use App\Infrastructure\Cache\RedisCache;

class InMemoryRedisCache extends RedisCache
{
    private static array $store = [];

    public function __construct()
    {
    }

    public function getClient()
    {
        return new class {
            public function get(string $key): ?string
            {
                $entry = InMemoryRedisCache::storeGet($key);
                if ($entry === null) {
                    return null;
                }
                if ($entry['expires_at'] !== 0 && $entry['expires_at'] <= time()) {
                    InMemoryRedisCache::storeDelete($key);
                    return null;
                }

                return $entry['value'];
            }

            public function setex(string $key, int $ttl, mixed $value): bool
            {
                InMemoryRedisCache::storeSet($key, (string) $value, time() + $ttl);

                return true;
            }

            public function del(string $key): int
            {
                $existed = InMemoryRedisCache::storeGet($key) !== null;
                InMemoryRedisCache::storeDelete($key);

                return $existed ? 1 : 0;
            }
        };
    }

    /** @internal exposed so the anonymous client class can reach module state */
    public static function storeGet(string $key): ?array
    {
        return self::$store[$key] ?? null;
    }

    /** @internal */
    public static function storeSet(string $key, string $value, int $expiresAt): void
    {
        self::$store[$key] = ['value' => $value, 'expires_at' => $expiresAt];
    }

    /** @internal */
    public static function storeDelete(string $key): void
    {
        unset(self::$store[$key]);
    }

    public static function reset(): void
    {
        self::$store = [];
    }
}

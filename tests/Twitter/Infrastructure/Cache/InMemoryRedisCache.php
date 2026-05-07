<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Cache;

use App\Twitter\Infrastructure\Cache\RedisCache;

class InMemoryRedisCache extends RedisCache
{
    public function __construct()
    {
    }

    public function getClient()
    {
        return new class {
            public function get(string $key): ?string
            {
                return null;
            }

            public function setex(string $key, int $ttl, mixed $value): bool
            {
                return true;
            }
        };
    }
}

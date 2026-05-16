<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Security\Authentication;

use App\Twitter\Infrastructure\Cache\RedisCache;
use Predis\Connection\ConnectionException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

use function hash;
use function serialize;
use function unserialize;

/**
 * Caches the apiKey → Member lookup in Redis so the security firewall does
 * not hit Postgres on every authenticated request.
 *
 * The inner provider (Symfony's Doctrine EntityUserProvider) is consulted on
 * cache miss; the returned UserInterface is then serialized and stored in
 * Redis under a key derived from sha256(apiKey) for a short TTL.
 *
 * Cache faults (Redis down, deserialization errors) degrade gracefully — the
 * request falls through to the inner provider so authentication keeps working.
 */
final class CachedApiKeyUserProvider implements UserProviderInterface
{
    private const CACHE_KEY_PREFIX = 'auth.member.';

    public function __construct(
        private readonly UserProviderInterface $inner,
        private readonly RedisCache $redisCache,
        private readonly int $ttlSeconds = 60,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $cacheKey = self::CACHE_KEY_PREFIX . hash('sha256', $identifier);

        $cached = $this->readCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $user = $this->inner->loadUserByIdentifier($identifier);

        $this->writeCache($cacheKey, $user);

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->inner->refreshUser($user);
    }

    public function supportsClass(string $class): bool
    {
        return $this->inner->supportsClass($class);
    }

    private function readCache(string $cacheKey): ?UserInterface
    {
        try {
            $client = $this->redisCache->getClient();
            $payload = $client->get($cacheKey);
        } catch (ConnectionException|Throwable) {
            return null;
        }

        if (!is_string($payload) || $payload === '') {
            return null;
        }

        $deserialized = @unserialize($payload, ['allowed_classes' => true]);

        return $deserialized instanceof UserInterface ? $deserialized : null;
    }

    private function writeCache(string $cacheKey, UserInterface $user): void
    {
        try {
            $client = $this->redisCache->getClient();
            $client->setex($cacheKey, $this->ttlSeconds, serialize($user));
        } catch (ConnectionException|Throwable) {
            // Best effort — auth must keep working even if Redis is down.
        }
    }
}

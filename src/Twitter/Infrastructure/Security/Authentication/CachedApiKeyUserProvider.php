<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Security\Authentication;

use App\Twitter\Infrastructure\Cache\RedisCache;
use InvalidArgumentException;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

use function function_exists;
use function hash;
use function random_bytes;
use function serialize;
use function sodium_crypto_generichash;
use function sodium_crypto_secretbox;
use function sodium_crypto_secretbox_open;
use function strlen;
use function substr;
use function unserialize;

use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
use const SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

/**
 * Caches the apiKey → Member lookup in Redis so the security firewall does
 * not hit Postgres on every authenticated request.
 *
 * The inner provider (Symfony's Doctrine EntityUserProvider) is consulted on
 * cache miss; the returned UserInterface is then serialized, encrypted with
 * libsodium's XSalsa20-Poly1305 (sodium_crypto_secretbox), and stored in
 * Redis under a key derived from sha256(apiKey) for a short TTL.
 *
 * Why encrypt: a PHP-serialized Member contains the apiKey itself and other
 * PII. A Redis RDB / AOF dump must not leak that. The encryption key is
 * derived once from APP_SECRET via BLAKE2b (sodium_crypto_generichash) and
 * never written to Redis.
 *
 * Cache faults (Redis down, decryption failure, deserialization errors)
 * degrade gracefully — the request falls through to the inner provider so
 * authentication keeps working even if every cache operation fails.
 */
final class CachedApiKeyUserProvider implements UserProviderInterface
{
    private const CACHE_KEY_PREFIX = 'auth.member.';

    private readonly string $encryptionKey;

    public function __construct(
        private readonly UserProviderInterface $inner,
        private readonly RedisCache $redisCache,
        #[SensitiveParameter] string $appSecret,
        private readonly int $ttlSeconds = 60,
    ) {
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException(
                'ext-sodium is required to encrypt cached UserInterface payloads.'
            );
        }
        if ($appSecret === '') {
            throw new InvalidArgumentException(
                'APP_SECRET must be a non-empty string to derive the cache encryption key.'
            );
        }
        // 32-byte symmetric key derived from APP_SECRET. Rotating APP_SECRET
        // makes every existing cache entry undecryptable — that's safe because
        // unserialization will fail and we fall through to the inner provider.
        $this->encryptionKey = sodium_crypto_generichash(
            $appSecret,
            '',
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES
        );
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
        } catch (Throwable) {
            return null;
        }

        if (!is_string($payload) || $payload === '') {
            return null;
        }

        $plain = $this->decrypt($payload);
        if ($plain === null) {
            // Tampered, wrong key, or pre-encryption legacy entry —
            // fall through to a fresh load from the inner provider.
            return null;
        }

        $deserialized = @unserialize($plain, ['allowed_classes' => true]);

        return $deserialized instanceof UserInterface ? $deserialized : null;
    }

    private function writeCache(string $cacheKey, UserInterface $user): void
    {
        try {
            $client = $this->redisCache->getClient();
            $client->setex($cacheKey, $this->ttlSeconds, $this->encrypt(serialize($user)));
        } catch (Throwable) {
            // Best effort — auth must keep working even if Redis is down.
        }
    }

    private function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return $nonce . sodium_crypto_secretbox($plain, $nonce, $this->encryptionKey);
    }

    private function decrypt(string $blob): ?string
    {
        if (strlen($blob) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce  = substr($blob, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($blob, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->encryptionKey);

        return $plain === false ? null : $plain;
    }
}

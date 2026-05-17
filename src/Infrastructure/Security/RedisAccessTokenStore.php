<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

final class RedisAccessTokenStore implements AccessTokenStore
{
    private const KEY_PREFIX = 'auth:bearer:';

    public function __construct(private readonly object $redis)
    {
    }

    public function put(string $tokenPlaintext, string $memberId, int $ttlSeconds): void
    {
        $now = time();
        $payload = json_encode([
            'm' => $memberId,
            'i' => $now,
            'e' => $now + $ttlSeconds,
        ], JSON_THROW_ON_ERROR);

        $this->redis->setex(self::KEY_PREFIX . hash('sha256', $tokenPlaintext), $ttlSeconds, $payload);
    }

    public function resolve(string $tokenPlaintext): ?AccessTokenRecord
    {
        $raw = $this->redis->get(self::KEY_PREFIX . hash('sha256', $tokenPlaintext));
        if ($raw === null || $raw === false) {
            return null;
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data) || !isset($data['m'], $data['i'], $data['e'])) {
            return null;
        }

        $expiresAt = (new \DateTimeImmutable())->setTimestamp((int) $data['e']);
        if ($expiresAt <= new \DateTimeImmutable()) {
            return null;
        }

        return new AccessTokenRecord(
            (string) $data['m'],
            (new \DateTimeImmutable())->setTimestamp((int) $data['i']),
            $expiresAt,
        );
    }

    public function revoke(string $tokenPlaintext): void
    {
        $this->redis->del(self::KEY_PREFIX . hash('sha256', $tokenPlaintext));
    }
}

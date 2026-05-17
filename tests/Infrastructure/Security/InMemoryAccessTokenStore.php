<?php
declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Security\AccessTokenRecord;
use App\Infrastructure\Security\AccessTokenStore;

final class InMemoryAccessTokenStore implements AccessTokenStore
{
    /** @var array<string, AccessTokenRecord> */
    private array $records = [];

    public function put(string $tokenPlaintext, string $memberId, int $ttlSeconds): void
    {
        $key = hash('sha256', $tokenPlaintext);
        $now = new \DateTimeImmutable();
        $this->records[$key] = new AccessTokenRecord(
            $memberId,
            $now,
            $now->modify(sprintf('%+d seconds', $ttlSeconds)),
        );
    }

    public function resolve(string $tokenPlaintext): ?AccessTokenRecord
    {
        $key = hash('sha256', $tokenPlaintext);
        $record = $this->records[$key] ?? null;
        if ($record === null) {
            return null;
        }
        if ($record->isExpired()) {
            unset($this->records[$key]);

            return null;
        }

        return $record;
    }

    public function revoke(string $tokenPlaintext): void
    {
        unset($this->records[hash('sha256', $tokenPlaintext)]);
    }

    public function hasKey(string $key): bool
    {
        return isset($this->records[$key]);
    }
}

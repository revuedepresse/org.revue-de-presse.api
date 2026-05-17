<?php
declare(strict_types=1);

namespace App\Security\Domain;

final class AccessTokenMinter
{
    public function __construct(
        private readonly AccessTokenStore $store,
        private readonly int $ttlSeconds = 900,
    ) {
    }

    public function mint(string $memberId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->store->put($token, $memberId, $this->ttlSeconds);

        return $token;
    }

    public function ttlSeconds(): int
    {
        return $this->ttlSeconds;
    }
}

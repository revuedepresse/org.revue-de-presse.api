<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

interface AccessTokenStore
{
    public function put(string $tokenPlaintext, string $memberId, int $ttlSeconds): void;

    public function resolve(string $tokenPlaintext): ?AccessTokenRecord;

    public function revoke(string $tokenPlaintext): void;
}

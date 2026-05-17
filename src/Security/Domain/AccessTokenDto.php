<?php
declare(strict_types=1);

namespace App\Security\Domain;

final readonly class AccessTokenDto
{
    public function __construct(
        public string $access_token,
        public string $token_type,
        public int $expires_in,
    ) {
    }
}

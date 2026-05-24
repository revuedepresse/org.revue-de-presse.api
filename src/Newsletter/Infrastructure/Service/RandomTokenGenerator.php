<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Service;

use App\Newsletter\Domain\Service\TokenGenerator;
use App\Newsletter\Domain\ValueObject\OpaqueToken;

final class RandomTokenGenerator implements TokenGenerator
{
    public function generate(): OpaqueToken
    {
        return OpaqueToken::fromRawBytes(random_bytes(32));
    }
}

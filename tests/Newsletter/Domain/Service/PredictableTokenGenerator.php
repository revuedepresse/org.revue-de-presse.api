<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Domain\Service;

use App\Newsletter\Domain\Service\TokenGenerator;
use App\Newsletter\Domain\ValueObject\OpaqueToken;

final class PredictableTokenGenerator implements TokenGenerator
{
    private int $counter = 0;

    public function generate(): OpaqueToken
    {
        $this->counter++;
        return OpaqueToken::fromRawBytes(str_pad(sprintf('%032d', $this->counter), 32, "\0"));
    }
}

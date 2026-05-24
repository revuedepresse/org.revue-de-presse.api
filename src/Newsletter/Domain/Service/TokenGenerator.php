<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

use App\Newsletter\Domain\ValueObject\OpaqueToken;

interface TokenGenerator
{
    public function generate(): OpaqueToken;
}

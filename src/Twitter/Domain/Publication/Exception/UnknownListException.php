<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Publication\Exception;

use InvalidArgumentException;

class UnknownListException extends InvalidArgumentException
{
    public static function throws(): void
    {
        throw new InvalidArgumentException('Unknown Publishers List');
    }
}

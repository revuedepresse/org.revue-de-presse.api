<?php
declare (strict_types=1);

namespace App\Ownership\Domain\Exception;

use InvalidArgumentException;

class UnknownListException extends InvalidArgumentException
{
    public static function throws(): void
    {
        throw new InvalidArgumentException('Unknown Publishers List');
    }
}

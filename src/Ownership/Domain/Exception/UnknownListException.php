<?php
declare (strict_types=1);

namespace App\NewsReview\Exception;

use InvalidArgumentException;

class UnknownPublishersListException extends InvalidArgumentException
{
    public static function throws(): void
    {
        throw new InvalidArgumentException('Unknown Publishers List');
    }
}
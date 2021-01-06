<?php
declare (strict_types=1);

namespace App\NewsReview\Domain\Exception;

use InvalidArgumentException;

class UnknownPublishersListException extends InvalidArgumentException
{
    public static function throws(): void
    {
        throw new self('Unknown publishers list');
    }
}
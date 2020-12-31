<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Security\Authorization;

use InvalidArgumentException;

class InvalidPinCodeException extends InvalidArgumentException
{
    public static function throws($message = null): void
    {
        throw new self($message ?? 'Invalid PIN code');
    }
}
<?php
declare(strict_types=1);

namespace App\Api\Exception;

use RuntimeException;

class UnavailableTokenException extends RuntimeException
{
    public static function throws(): void
    {
        throw new self('There is access token available');
    }
}
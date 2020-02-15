<?php
declare(strict_types=1);

namespace App\Twitter\Exception;

use RuntimeException;

/**
 * @package App\Twitter\Exception
 */
class InvalidTokensException extends RuntimeException
{
    public static function throws(): void
    {
        throw new self('Invalid tokens configured to access Twitter API');
    }
}
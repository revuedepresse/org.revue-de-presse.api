<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Exception;

use RuntimeException;

class InvalidTwitterErrorCodeException extends RuntimeException
{
    /**
     * @param $message
     *
     * @throws InvalidTwitterErrorCodeException
     */
    public static function throws($message): void
    {
        throw new self($message);
    }
}
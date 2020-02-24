<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Exception;

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
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Exception;

use RuntimeException;

class EmptyListException extends RuntimeException
{
    /**
     * @param string $message
     */
    public static function throws(string $message): void
    {
        throw new self($message);
    }
}
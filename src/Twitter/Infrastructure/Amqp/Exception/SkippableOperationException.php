<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Exception;

use RuntimeException;

class SkippableOperationException extends RuntimeException
{
    public static function throws($message): void
    {
        throw new self($message);
    }
}
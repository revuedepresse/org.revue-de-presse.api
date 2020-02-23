<?php
declare(strict_types=1);

namespace App\Amqp\Exception;

use RuntimeException;

class SkippableOperationException extends RuntimeException
{
    public static function throws($message): void
    {
        throw new self($message);
    }
}
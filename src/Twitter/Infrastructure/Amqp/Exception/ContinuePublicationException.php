<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Exception;

use Exception;
use Throwable;

class ContinuePublicationException extends Exception
{
    public static function throws(string $message, Throwable $previous): void
    {
        throw new self($message, $previous->getCode(), $previous);
    }
}
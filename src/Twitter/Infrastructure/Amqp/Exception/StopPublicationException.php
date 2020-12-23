<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Exception;

use Throwable;

class StopPublicationException extends \Exception
{
    /**
     * @param $message
     * @param Throwable $previous
     * @throws StopPublicationException
     */
    public static function throws($message, Throwable $previous): void
    {
        throw new self($message, $previous->getCode(), $previous);
    }
}
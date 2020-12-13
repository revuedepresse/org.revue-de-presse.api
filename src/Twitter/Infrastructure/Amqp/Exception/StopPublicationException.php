<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Exception;

class StopPublicationException extends \Exception
{
    /**
     * @param $message
     *
     * @throws StopPublicationException
     */
    public static function throws($message): void
    {
        throw new self($message);
    }
}
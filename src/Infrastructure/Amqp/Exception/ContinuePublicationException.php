<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Exception;

class ContinuePublicationException extends \Exception
{
    /**
     * @param $message
     *
     * @throws ContinuePublicationException
     */
    public static function throws($message): void
    {
        throw new self($message);
    }
}
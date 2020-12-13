<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Exception;

/**
 * @package App\Amqp
 */
class UnexpectedOwnershipException extends \RuntimeException
{
    /**
     * @param string $message
     */
    public static function throws(string $message): void
    {
        throw new self($message);
    }
}

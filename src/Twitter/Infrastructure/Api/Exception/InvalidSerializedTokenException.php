<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Exception;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class InvalidSerializedTokenException extends \Exception
{
    /**
     * @param $message
     *
     * @throws InvalidSerializedTokenException
     */
    public static function throws($message): void
    {
        throw new self($message);
    }
}
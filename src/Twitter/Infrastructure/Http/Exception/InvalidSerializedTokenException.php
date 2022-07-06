<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Exception;

/**
 * @author revue-de-presse.org <thierrymarianne@users.noreply.github.com>
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
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Exception;

use Exception;

class UnexpectedAccessTokenProperties extends Exception
{
    /**
     * @throws \App\Twitter\Infrastructure\Http\Exception\UnexpectedAccessTokenProperties
     */
    public static function throws($message, $code = 0, Exception $previous = null): void
    {
        throw new self($message, $code, $previous);
    }
}

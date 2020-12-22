<?php

namespace App\Twitter\Infrastructure\Exception;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class EmptyErrorCodeException extends UnavailableResourceException
{
    /**
     * @param $message
     * @param $code
     * @param \Exception|null $previous
     * @return EmptyErrorCodeException
     */
    public static function encounteredWhenUsingToken($message, $code, \Exception $previous = null)
    {
        return new self($message, $code, $previous);
    }
}

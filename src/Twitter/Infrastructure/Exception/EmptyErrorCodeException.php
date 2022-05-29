<?php

namespace App\Twitter\Infrastructure\Exception;

/**
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

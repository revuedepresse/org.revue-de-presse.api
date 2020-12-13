<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Exception;

/**
 * @package App\Twitter\Infrastructure\Exception
 */
class UnknownApiAccessException extends UnavailableResourceException
{
    /**
     * @param string $error
     *
     * @throws UnknownApiAccessException
     */
    public static function throws(string $error): void
    {
        throw new self(
            sprintf(
                'An unknown exception has occurred with error "%s"',
                $error
            )
        );
    }
}
<?php

namespace App\Twitter\Infrastructure\Exception;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SuspendedAccountException extends UnavailableResourceException
{
    public $screenName;

    /**
     * @param string          $screenName
     * @param int             $code
     * @param \Throwable|null $previous
     * @return SuspendedAccountException
     * @throws SuspendedAccountException
     */
    public static function raiseExceptionAboutSuspendedMemberHavingScreenName(
        string $screenName,
        int $code = 0,
        \Throwable $previous = null
    ): self {
        $exception = new self(
            sprintf(
                'Member with screen name "%s" is suspended',
                $screenName
            ),
            $code,
            $previous
        );
        $exception->screenName = $screenName;

        throw $exception;
    }
}

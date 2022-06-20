<?php

namespace App\Twitter\Infrastructure\Exception;

/**
 * @author revue-de-presse.org <thierrymarianne@users.noreply.github.com>
 */
class SuspendedAccountException extends UnavailableResourceException
{
    public string $screenName;
    public string $twitterId;

    public static function raiseExceptionAboutSuspendedMemberHavingScreenName(
        string $screenName,
        string $twitterId,
        int $code = 0,
        \Throwable $previous = null
    ): void {
        $exception = new self(
            sprintf(
                'Member with screen name "%s" is suspended',
                $screenName
            ),
            $code,
            $previous
        );
        $exception->screenName = $screenName;
        $exception->twitterId = $twitterId;

        throw $exception;
    }
}

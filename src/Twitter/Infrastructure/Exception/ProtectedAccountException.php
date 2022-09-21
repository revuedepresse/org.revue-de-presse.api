<?php

namespace App\Twitter\Infrastructure\Exception;

class ProtectedAccountException extends UnavailableResourceException
{
    public string $screenName;
    public string $twitterId;

    public static function raiseExceptionAboutProtectedMemberHavingScreenName(
        string $screenName,
        string $twitterId,
        int $code = 0
    ): void {
        $exception = new self(sprintf(
            'Member with screen name "%s" is protected',
            $screenName
        ), $code);
        $exception->screenName = $screenName;
        $exception->twitterId = $twitterId;

        throw $exception;
    }
}

<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Exception;

class NotFoundMemberException extends UnavailableResourceException
{
    public string $screenName;
    public string $twitterId;

    public static function raiseExceptionAboutNotFoundMemberHavingScreenName(
        string $screenName,
        string $twitterId,
        int $code = 0,
        \Throwable $previous = null
    ): void {
        $exception = new self(
            sprintf(
                'Could not find member with screen name "%s"',
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

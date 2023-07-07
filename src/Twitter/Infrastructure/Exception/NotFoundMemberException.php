<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Exception;

use App\Twitter\Domain\Http\TwitterAPIAwareInterface;

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

    public static function raiseExceptionAboutTemporarilyNotFoundMemberHavingScreenName(
        string $screenName,
        string $twitterId,
        int $code = TwitterAPIAwareInterface::ERROR_HTTP_NOT_FOUND,
        \Throwable $previous = null
    ): void {
        $exception = new self(
            sprintf(
                'Could not find temporarily member with screen name "%s" (See github.com/zedeus/nitter/issues/919)',
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

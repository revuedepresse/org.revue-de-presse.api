<?php

namespace App\Twitter\Exception;

class NotFoundMemberException extends UnavailableResourceException
{
    public $screenName;

    /**
     * @param string          $screenName
     * @param int             $code
     * @param \Throwable|null $previous
     * @return NotFoundMemberException
     * @throws NotFoundMemberException
     */
    public static function raiseExceptionAboutNotFoundMemberHavingScreenName(
        string $screenName,
        int $code = 0,
        \Throwable $previous = null
    ): self {
        $exception = new self(
            sprintf(
                'Could not find member with screen name "%s"',
                $screenName
            ),
            $code,
            $previous
        );
        $exception->screenName = $screenName;

        throw $exception;
    }
}

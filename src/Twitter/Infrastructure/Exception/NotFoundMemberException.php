<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Exception;

class NotFoundMemberException extends UnavailableResourceException
{
    public string $screenName;

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

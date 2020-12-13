<?php

namespace App\Twitter\Infrastructure\Exception;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ProtectedAccountException extends UnavailableResourceException
{
    public $screenName;

    /**
     * @param string $screenName
     * @param int    $code
     * @return ProtectedAccountException
     * @throws ProtectedAccountException
     */
    public static function raiseExceptionAboutProtectedMemberHavingScreenName(
        string $screenName,
        int $code = 0
    ): self {
        $exception = new self(sprintf(
            'Member with screen name "%s" is protected',
            $screenName
        ), $code);
        $exception->screenName = $screenName;

        throw $exception;
    }
}

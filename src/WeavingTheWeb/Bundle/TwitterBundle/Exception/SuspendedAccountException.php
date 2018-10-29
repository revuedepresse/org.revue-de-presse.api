<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Exception;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SuspendedAccountException extends UnavailableResourceException
{
    public $screenName;

    /**
     * @param string $screenName
     * @param int    $code
     * @return SuspendedAccountException
     * @throws SuspendedAccountException
     */
    public static function raiseExceptionAboutSuspendedMemberHavingScreenName(
        string $screenName,
        int $code = 0
    ): self {
        $exception = new self(sprintf(
            'Member with screen name "%s" is suspended',
            $screenName
        ), $code);
        $exception->screenName = $screenName;

        throw $exception;
    }
}

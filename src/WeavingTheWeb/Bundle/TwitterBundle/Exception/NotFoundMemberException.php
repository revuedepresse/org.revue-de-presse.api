<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Exception;

class NotFoundMemberException extends UnavailableResourceException
{
    public $screenName;

    /**
     * @param string $screenName
     * @param int    $code
     * @return NotFoundMemberException
     * @throws NotFoundMemberException
     */
    public static function raiseExceptionAboutNotFoundMemberHavingScreenName(
        string $screenName,
        int $code = 0
    ): self {
        $exception = new self(sprintf(
            'Could not find member with screen name "%s"',
            $screenName
        ), $code);
        $exception->screenName = $screenName;

        throw $exception;
    }
}

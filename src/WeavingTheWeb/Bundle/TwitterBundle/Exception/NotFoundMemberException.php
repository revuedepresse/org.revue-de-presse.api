<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Exception;

class NotFoundMemberException extends UnavailableResourceException
{
    public $screenName;

    public static function raiseExceptionAboutNotFoundMemberHavingScreenName($screenName): self
    {
        $exception = new self(sprintf(
            'Could not find member with screen name "%s"',
            $screenName
        ));
        $exception->screenName = $screenName;

        throw $exception;
    }
}

<?php

namespace App\Twitter\Infrastructure\Http\Client\Exception;

class InvalidMemberTimelineException extends \Exception
{
    /**
     * @throws InvalidMemberTimelineException
     */
    public static function throws(string $memberScreenName): self
    {
        throw new self(vsprintf('Invalid member timeline for %s', [$memberScreenName]));
    }
}
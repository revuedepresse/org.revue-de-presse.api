<?php

namespace App\Twitter\Domain\Http\Client\Fallback\Exception;

class FallbackHttpAccessException extends \Exception
{
    public const INVALID_MEMBER_PROFILE = 10;

    /**
     * @throws \App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException
     */
    public static function throwInvalidMemberProfileException($memberUsername)
    {
        throw new FallbackHttpAccessException(
            sprintf('Could not fetch legacy member profile for member having screenane "%s".', $memberUsername),
            FallbackHttpAccessException::INVALID_MEMBER_PROFILE
        );
    }
}
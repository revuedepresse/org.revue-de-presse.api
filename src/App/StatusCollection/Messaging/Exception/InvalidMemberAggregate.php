<?php

namespace App\StatusCollection\Messaging\Exception;

class InvalidMemberAggregate extends \Exception
{
    /**
     * @param string $username
     */
    public static function guardAgainstInvalidUsername(string $username)
    {
        throw new self(sprintf('Could not find aggregate for member having username "%s"', $username));
    }
}

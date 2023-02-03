<?php

namespace App\QualityAssurance\Infrastructure\Console;
use Exception;

class AvatarNotFoundException extends Exception
{
    /**
     * @throws \App\QualityAssurance\Infrastructure\Console\AvatarNotFoundException
     */
    public static function throws(string $tweetId)
    {
        throw new self(sprintf('Could not find avatar for tweet having id "%s"', $tweetId));
    }
}
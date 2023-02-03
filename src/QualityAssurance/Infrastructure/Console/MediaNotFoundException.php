<?php

namespace App\QualityAssurance\Infrastructure\Console;

use App\QualityAssurance\Infrastructure\Console\MediaNotFoundException as NotFoundException;
use Exception;

class MediaNotFoundException extends Exception
{
    /**
     * @throws NotFoundException
     */
    public static function throws(string $tweetId)
    {
        throw new self(sprintf('Could not find media for tweet having id "%s"', $tweetId));
    }
}
<?php

namespace App\QualityAssurance\Infrastructure\Console;

use App\QualityAssurance\Infrastructure\Console\TweetNotFoundException as NotFoundException;
use Exception;

class TweetNotFoundException extends Exception
{
    public readonly TweetInterface $tweet;

    public function __construct(
        TweetInterface $tweet,
        $previous = null
    )
    {
        $this->tweet = $tweet;

        parent::__construct(
            sprintf(
                'Could not find tweet having id "%s"',
                $tweet->tweetId()
            ),
            0,
            $previous
        );
    }

    /**
     * @throws NotFoundException
     */
    public static function throws(TweetInterface $tweet, $previous = null)
    {
        throw new self($tweet, $previous);
    }
}
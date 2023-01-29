<?php

namespace App\Tests\QualityAssurance\Infrastructure\Curation\Curator;

use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Domain\Curation\Curator\TweetCuratorInterface;

class TweetCurator implements TweetCuratorInterface
{

    /**
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     */
    public function collectSingleTweet(string $identifier): mixed
    {
        throw new TweetNotFoundException(sprintf(
            'Could not find tweet having identifier %s', $identifier
        ));
    }

    public function curateTweets(array $options, $greedy = false, $discoverPublicationsWithMaxId = true): bool
    {
        return false;
    }
}
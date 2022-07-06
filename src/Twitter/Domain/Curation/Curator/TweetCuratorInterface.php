<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Curator;

interface TweetCuratorInterface
{
    public function curateTweets(
        array $options,
        $greedy = false,
        $discoverPublicationsWithMaxId = true
    ): bool;
}

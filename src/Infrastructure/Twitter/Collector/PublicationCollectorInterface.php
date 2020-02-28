<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Status\LikedStatusCollectionAwareInterface;

interface PublicationCollectorInterface extends LikedStatusCollectionAwareInterface
{
    public function collect(array $options, $greedy = false, $discoverPastTweets = true): bool;
}

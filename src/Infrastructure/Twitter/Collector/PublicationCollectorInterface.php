<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

interface PublicationCollectorInterface
{
    public function collect(array $options, $greedy = false, $discoverPastTweets = true): bool;
}

<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Collector;

interface PublicationCollectorInterface
{
    public function collect(
        array $options,
        $greedy = false,
        $discoverPublicationsWithMaxId = true
    ): bool;
}

<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Domain\Collection\CollectionStrategyInterface;

interface InterruptibleCollectDeciderInterface
{
    public function decideWhetherCollectShouldBeSkipped(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    );

    public function delayingConsumption(): bool;

    public function updateExtremum(
        array $options,
        bool $discoverPastTweets = true
    );
}
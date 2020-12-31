<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Collector;

use App\Twitter\Domain\Curation\CollectionStrategyInterface;

interface InterruptibleCollectDeciderInterface
{
    public function decideWhetherCollectShouldBeSkipped(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    );

    public function delayingConsumption(): bool;
}
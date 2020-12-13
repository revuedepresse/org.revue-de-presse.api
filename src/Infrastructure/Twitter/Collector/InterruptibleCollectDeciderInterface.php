<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Domain\Curation\CollectionStrategyInterface;

interface InterruptibleCollectDeciderInterface
{
    public function decideWhetherCollectShouldBeSkipped(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    );

    public function delayingConsumption(): bool;
}
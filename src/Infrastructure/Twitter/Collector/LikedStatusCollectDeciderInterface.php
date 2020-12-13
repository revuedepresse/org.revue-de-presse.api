<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Publication\PublishersListInterface;

interface LikedStatusCollectDeciderInterface
{
    public function shouldSkipLikedStatusCollect(
        array $options,
        array $statuses,
        CollectionStrategyInterface $collectionStrategy,
        ?PublishersListInterface $publishersList
    ): bool;
}
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Collector;

use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Twitter\Domain\Publication\PublishersListInterface;

interface LikedStatusCollectDeciderInterface
{
    public function shouldSkipLikedStatusCollect(
        array $options,
        array $statuses,
        CollectionStrategyInterface $collectionStrategy,
        ?PublishersListInterface $publishersList
    ): bool;
}
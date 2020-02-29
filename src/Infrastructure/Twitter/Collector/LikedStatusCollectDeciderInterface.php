<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Publication\PublicationListInterface;

interface LikedStatusCollectDeciderInterface
{
    public function shouldSkipLikedStatusCollect(
        array $options,
        array $statuses,
        CollectionStrategyInterface $collectionStrategy,
        ?PublicationListInterface $publicationList
    ): bool;
}
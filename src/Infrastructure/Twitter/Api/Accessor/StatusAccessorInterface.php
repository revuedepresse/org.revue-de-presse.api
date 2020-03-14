<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Domain\Collection\CollectionStrategyInterface;

interface StatusAccessorInterface
{
    public function updateExtremum(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        bool $discoverPublicationWithMaxId = true
    ): array;
}
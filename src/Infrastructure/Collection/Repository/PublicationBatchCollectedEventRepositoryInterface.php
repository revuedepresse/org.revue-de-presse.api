<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Collection\CollectionStrategyInterface;

interface PublicationBatchCollectedEventRepositoryInterface
{
    public function collectedPublicationBatch(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    );
}
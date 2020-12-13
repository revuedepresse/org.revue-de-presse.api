<?php

declare(strict_types=1);

namespace App\Domain\Curation\Repository;

use App\Domain\Curation\CollectionStrategyInterface;

interface PublicationBatchCollectedEventRepositoryInterface
{
    public function collectedPublicationBatch(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    );
}
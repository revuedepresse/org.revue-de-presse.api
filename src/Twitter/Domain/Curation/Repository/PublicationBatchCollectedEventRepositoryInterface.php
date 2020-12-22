<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Curation\CollectionStrategyInterface;

interface PublicationBatchCollectedEventRepositoryInterface
{
    public function collectedPublicationBatch(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    );
}
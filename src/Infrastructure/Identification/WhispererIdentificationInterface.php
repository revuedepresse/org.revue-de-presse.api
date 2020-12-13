<?php
declare(strict_types=1);

namespace App\Infrastructure\Identification;

use App\Domain\Curation\CollectionStrategyInterface;

interface WhispererIdentificationInterface
{
    public function identifyWhisperer(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        string $screenName,
        int $lastCollectionBatchSize
    ): bool;
}
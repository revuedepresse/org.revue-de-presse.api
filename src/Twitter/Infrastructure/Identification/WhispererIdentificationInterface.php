<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Identification;

use App\Twitter\Domain\Curation\CollectionStrategyInterface;

interface WhispererIdentificationInterface
{
    public function identifyWhisperer(
        CollectionStrategyInterface $collectionStrategy,
        array $options,
        string $screenName,
        int $lastCollectionBatchSize
    ): bool;
}
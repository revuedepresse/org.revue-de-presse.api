<?php
declare(strict_types=1);

namespace App\Infrastructure\Identification;

interface WhispererIdentificationInterface
{
    public function identifyWhisperer(
        string $screenName,
        int $totalCollectedStatuses,
        ?int $lastCollectionBatchSize
    ): bool;
}
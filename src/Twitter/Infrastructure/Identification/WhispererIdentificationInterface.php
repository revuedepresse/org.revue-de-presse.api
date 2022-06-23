<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Identification;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;

interface WhispererIdentificationInterface
{
    public function identifyWhisperer(
        CurationSelectorsInterface $selectors,
        array                      $options,
        string                     $screenName,
        int                        $lastCollectionBatchSize
    ): bool;
}
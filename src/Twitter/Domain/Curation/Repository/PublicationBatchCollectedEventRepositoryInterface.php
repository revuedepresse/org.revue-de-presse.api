<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;

interface PublicationBatchCollectedEventRepositoryInterface
{
    public function collectedPublicationBatch(
        CurationSelectorsInterface $selectors,
        array                      $options
    );
}
<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Resource\OwnershipCollection;
use App\Twitter\Domain\Api\ApiAccessorInterface;

interface OwnershipBatchCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';
    public const OPTION_NEXT_PAGE = 'next_page';

    public function collectedOwnershipBatch(
        ApiAccessorInterface $accessor,
        array $options
    ): OwnershipCollection;
}
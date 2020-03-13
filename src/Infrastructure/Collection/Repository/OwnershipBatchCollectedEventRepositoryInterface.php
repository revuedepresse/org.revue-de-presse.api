<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Resource\OwnershipCollection;
use App\Twitter\Api\ApiAccessorInterface;

interface OwnershipBatchCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';
    public const OPTION_NEXT_PAGE = 'next_page';

    public function collectedOwnershipBatch(
        ApiAccessorInterface $accessor,
        array $options
    ): OwnershipCollection;
}
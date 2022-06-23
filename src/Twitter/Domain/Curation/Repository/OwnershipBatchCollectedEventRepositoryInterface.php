<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Api\Accessor\OwnershipAccessorInterface;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;

interface OwnershipBatchCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';
    public const OPTION_NEXT_PAGE = 'next_page';

    public function collectedOwnershipBatch(
        OwnershipAccessorInterface $accessor,
        ListSelectorInterface $selector
    ): OwnershipCollectionInterface;

    public function byScreenName(string $screenName): OwnershipCollectionInterface;
}
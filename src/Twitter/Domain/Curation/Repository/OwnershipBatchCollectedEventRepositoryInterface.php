<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Http\Accessor\OwnershipAccessorInterface;
use App\Twitter\Domain\Resource\OwnershipCollectionInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;

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

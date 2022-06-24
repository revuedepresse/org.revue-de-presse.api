<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Http\Client\ListAwareHttpClientInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;

interface ListsBatchCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';
    public const OPTION_NEXT_PAGE = 'next_page';

    public function collectedOwnershipBatch(
        ListAwareHttpClientInterface $accessor,
        ListSelectorInterface        $selector
    ): OwnershipCollectionInterface;

    public function byScreenName(string $screenName): OwnershipCollectionInterface;
}
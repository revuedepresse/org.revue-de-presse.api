<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Http\Resource\MemberCollectionInterface;
use App\Twitter\Domain\Http\Accessor\ApiAccessorInterface;

interface PublishersListCollectedEventRepositoryInterface
{
    public const OPTION_PUBLISHERS_LIST_ID = 'list_id';
    public const OPTION_PUBLISHERS_LIST_NAME = 'list_name';

    public function collectedPublishersList(
        ApiAccessorInterface $accessor,
        array $options
    ): MemberCollectionInterface;

    public function byListId(string $listId): MemberCollectionInterface;
}

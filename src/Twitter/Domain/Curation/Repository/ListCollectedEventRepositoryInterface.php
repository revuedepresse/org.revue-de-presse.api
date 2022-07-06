<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Resource\MemberCollectionInterface;

interface ListCollectedEventRepositoryInterface
{
    public const OPTION_PUBLISHERS_LIST_ID   = 'list_id';
    public const OPTION_PUBLISHERS_LIST_NAME = 'list_name';

    public function collectedListOwnedByMember(
        HttpClientInterface $accessor,
        array               $options
    ): MemberCollectionInterface;

    public function byListId(string $listId): MemberCollectionInterface;
}
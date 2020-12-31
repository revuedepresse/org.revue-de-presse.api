<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Resource\MemberCollection;
use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;

interface PublishersListCollectedEventRepositoryInterface
{
    public const OPTION_PUBLISHERS_LIST_ID = 'list_id';
    public const OPTION_PUBLISHERS_LIST_NAME = 'list_name';

    public function collectedPublishersList(
        ApiAccessorInterface $accessor,
        array $options
    ): MemberCollection;
}
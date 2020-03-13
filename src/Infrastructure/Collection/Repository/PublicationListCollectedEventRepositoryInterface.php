<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Resource\MemberCollection;
use App\Twitter\Api\ApiAccessorInterface;

interface PublicationListCollectedEventRepositoryInterface
{
    public const OPTION_PUBLICATION_LIST_ID = 'list_id';
    public const OPTION_PUBLICATION_LIST_NAME = 'list_name';

    public function collectedPublicationList(
        ApiAccessorInterface $accessor,
        array $options
    ): MemberCollection;
}
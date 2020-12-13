<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Domain\Resource\MemberCollection;
use App\Twitter\Api\ApiAccessorInterface;

interface PublishersListCollectedEventRepositoryInterface
{
    public const OPTION_publishers_list_ID = 'list_id';
    public const OPTION_publishers_list_NAME = 'list_name';

    public function collectedPublishersList(
        ApiAccessorInterface $accessor,
        array $options
    ): MemberCollection;
}
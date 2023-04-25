<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Repository;

use App\Twitter\Domain\Http\Accessor\ApiAccessorInterface;

interface MemberFriendsCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';

    public function collectedMemberFriends(
        ApiAccessorInterface $accessor,
        array $options
    );
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessorInterface;
use App\Infrastructure\Twitter\Api\Resource\FriendsList;

interface MemberFriendsListCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';
    public const OPTION_CURSOR = 'cursor';

    public function aggregatedMemberFriendsLists(
        FriendsAccessorInterface $accessor,
        string $screenName
    ): FriendsList;

    public function collectedMemberFriendsList(
        FriendsAccessorInterface $accessor,
        array $options
    ): FriendsList;
}
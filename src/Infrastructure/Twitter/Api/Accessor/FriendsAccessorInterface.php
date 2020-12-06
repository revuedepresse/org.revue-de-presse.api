<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Resource\FriendsList;

interface FriendsAccessorInterface
{
    public function getMemberFriendsListAtCursor(string $screenName, string $cursor): FriendsList;

    public function getMemberFriendsListAtDefaultCursor(string $screenName): FriendsList;

    public function getMemberFriendsList(string $screenName): FriendsList;
}
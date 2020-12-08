<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Resource\FriendsList;
use Closure;

interface FriendsAccessorInterface
{
    public function getMemberFriendsListAtCursor(string $screenName, string $cursor, Closure $onFinishCollection = null): FriendsList;

    public function getMemberFriendsListAtDefaultCursor(string $screenName, Closure $onFinishCollection = null): FriendsList;

    public function getMemberFriendsList(string $screenName): FriendsList;
}
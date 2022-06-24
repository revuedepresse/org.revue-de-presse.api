<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Mutator;

use App\Twitter\Infrastructure\Http\Resource\MemberCollection;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;

interface FriendshipMutatorInterface
{
    public function unfollowMembers(
        MemberCollection $memberCollection,
        MemberInterface $subscriber
    ): CollectionInterface;
}
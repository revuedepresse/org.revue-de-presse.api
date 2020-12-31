<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Mutator;

use App\Twitter\Domain\Resource\MemberCollection;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;

interface FriendshipMutatorInterface
{
    public function unfollowMembers(
        MemberCollection $memberCollection,
        MemberInterface $subscriber
    ): CollectionInterface;
}
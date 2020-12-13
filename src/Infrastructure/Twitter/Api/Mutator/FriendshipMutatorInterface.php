<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Mutator;

use App\Domain\Resource\MemberCollection;
use App\Membership\Domain\Entity\MemberInterface;
use App\Infrastructure\Operation\Collection\CollectionInterface;

interface FriendshipMutatorInterface
{
    public function unfollowMembers(
        MemberCollection $memberCollection,
        MemberInterface $subscriber
    ): CollectionInterface;
}
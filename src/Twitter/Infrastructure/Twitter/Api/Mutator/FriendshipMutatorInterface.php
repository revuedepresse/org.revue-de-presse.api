<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Mutator;

use App\Twitter\Domain\Resource\MemberCollection;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;

interface FriendshipMutatorInterface
{
    public function unfollowMembers(
        MemberCollection $memberCollection,
        MemberInterface $subscriber
    ): CollectionInterface;
}
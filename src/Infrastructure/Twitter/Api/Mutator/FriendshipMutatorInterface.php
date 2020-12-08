<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Mutator;

use App\Domain\Resource\MemberCollection;

interface FriendshipMutatorInterface
{
    public function unfollowMembers(
        MemberCollection $memberCollection
    );
}
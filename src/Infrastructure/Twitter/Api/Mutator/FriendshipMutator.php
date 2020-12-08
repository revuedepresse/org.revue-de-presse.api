<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Mutator;

use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;

class FriendshipMutator implements FriendshipMutatorInterface
{
    use ApiAccessorTrait;

    public function unfollowMembers(
        MemberCollection $memberCollection
    ): void {
        $memberCollection->map(function(MemberIdentity $identity) {
            $this->apiAccessor->contactEndpoint(
                $this->getDestroyFriendshipsEndpoint($identity->screenName())
            );

            return $identity;
        });
    }

    private function getDestroyFriendshipsEndpoint(string $screenName): string
    {
        return strtr(
            $this->apiAccessor->getApiBaseUrl() . '/friendships/destroy.json?screen_name={{ screen_name }}',
            ['{{ screen_name }}' => $screenName]
        );
    }
}
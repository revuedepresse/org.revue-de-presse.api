<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Mutator;

use Abraham\TwitterOAuth\TwitterOAuthException;
use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Infrastructure\Api\Resource\MemberCollection;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Subscription\MemberSubscriptionRepositoryTrait;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;

class FriendshipMutator implements FriendshipMutatorInterface
{
    use ApiAccessorTrait;
    use LoggerTrait;
    use MemberRepositoryTrait;
    use MemberSubscriptionRepositoryTrait;

    public function unfollowMembers(
        MemberCollection $memberCollection,
        MemberInterface $subscriber
    ): CollectionInterface {
        return $memberCollection->map(function(MemberIdentity $identity) use ($subscriber) {
            try {
                $this->apiAccessor->contactEndpoint(
                    $this->getDestroyFriendshipEndpointForMemberHavingId($identity->id())
                );

                $this->memberSubscriptionRepository->cancelMemberSubscription(
                    $subscriber,
                    $this->memberRepository->findOneBy([
                        'twitterID' => $identity->id()
                    ])
                );
            } catch (UnavailableResourceException|TwitterOAuthException $e) {
                $this->logger->error($e->getMessage());
            }

            return $identity;
        });
    }

    private function getDestroyFriendshipEndpoint(string $screenName): string
    {
        return strtr(
            $this->apiAccessor->getApiBaseUrl() . '/friendships/destroy.json?screen_name={{ screen_name }}',
            ['{{ screen_name }}' => $screenName]
        );
    }

    private function getDestroyFriendshipEndpointForMemberHavingId(string $id): string
    {
        return strtr(
            $this->apiAccessor->getApiBaseUrl() . '/friendships/destroy.json?user_id={{ user_id }}',
            ['{{ user_id }}' => $id]
        );
    }
}
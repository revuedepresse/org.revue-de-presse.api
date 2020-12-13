<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Mutator;

use Abraham\TwitterOAuth\TwitterOAuthException;
use App\Twitter\Domain\Resource\MemberCollection;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Subscription\MemberSubscriptionRepositoryTrait;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;

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
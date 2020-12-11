<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Mutator;

use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\MemberIdentity;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Infrastructure\DependencyInjection\Subscription\MemberSubscriptionRepositoryTrait;
use App\Membership\Entity\MemberInterface;
use App\Operation\Collection\CollectionInterface;
use App\Twitter\Exception\UnavailableResourceException;

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
                    $this->getDestroyFriendshipsEndpoint($identity->screenName())
                );

                $this->memberSubscriptionRepository->cancelMemberSubscription(
                    $subscriber,
                    $this->memberRepository->findOneBy([
                        'twitter_username' => $identity->screenName()
                    ])
                );
            } catch (UnavailableResourceException $e) {
                $this->logger->error($e->getMessage());
            }

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
<?php

namespace App\Membership\Infrastructure\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Domain\Repository\PublishersListSubscriptionRepositoryInterface;
use App\Membership\Infrastructure\Entity\AggregateSubscription;
use App\Membership\Infrastructure\Entity\MemberSubscription;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Infrastructure\PublishersList\Entity\MemberAggregateSubscription;
use App\Twitter\Infrastructure\PublishersList\Repository\MemberAggregateSubscriptionRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
use Psr\Log\LoggerInterface;

class AggregateSubscriptionRepository extends ServiceEntityRepository implements PublishersListSubscriptionRepositoryInterface
{
    public HttpClientInterface $accessor;

    public MemberAggregateSubscriptionRepository $memberAggregateSubscriptionRepository;

    public MemberSubscriptionRepository $memberSubscriptionRepository;

    public NetworkRepositoryInterface $networkRepository;

    public LoggerInterface $logger;

    /**
     * @param MemberAggregateSubscription $memberAggregateSubscription
     * @param MemberInterface $subscription
     * @return AggregateSubscription
     */
    public function make(
        MemberAggregateSubscription $memberAggregateSubscription,
        MemberInterface $subscription
    ) {
        $aggregateSubscription = $this->findOneBy([
            'memberAggregateSubscription' => $memberAggregateSubscription,
            'subscription' => $subscription
        ]);

        if (!($aggregateSubscription instanceof AggregateSubscription)) {
            $aggregateSubscription = new AggregateSubscription($memberAggregateSubscription, $subscription);
        }

        return $this->saveAggregateSubscription($aggregateSubscription);
    }

    /**
     * @param AggregateSubscription $aggregateSubscription
     * @return AggregateSubscription
     */
    public function saveAggregateSubscription(AggregateSubscription $aggregateSubscription)
    {
        $this->getEntityManager()->persist($aggregateSubscription);
        $this->getEntityManager()->flush();

        return $aggregateSubscription;
    }

    /**
     * @param string $aggregateName
     * @return array
     * @throws Exception
     */
    public function findSubscriptionsByAggregateName(string $aggregateName)
    {
        $memberAggregateSubscription = $this->memberAggregateSubscriptionRepository
            ->findOneBy(['listName' => $aggregateName])
        ;

        if (!($memberAggregateSubscription instanceof MemberAggregateSubscription)) {
            throw new Exception(
                sprintf(
                    'No member aggregate subscription could be found for name "%s"',
                    $aggregateName
                )
            );
        }

        return $this->findBy(['memberAggregateSubscription' => $memberAggregateSubscription]);
    }


    /**
     * @param string $memberName
     * @param string $aggregateName
     */
    public function letMemberSubscribeToAggregate(string $memberName, string $aggregateName): void
    {
        $member = $this->accessor->ensureMemberHavingNameExists($memberName);

        try {
            $subscriptions = $this->findSubscriptionsByAggregateName($aggregateName);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());

            return;
        }

        array_walk(
            $subscriptions,
            function (AggregateSubscription $subscription) use ($member) {
                $existingSubscription = $this->memberSubscriptionRepository->findOneBy([
                    'member' => $member,
                    'subscription' => $subscription->subscription
                ]);

                if ($existingSubscription instanceof MemberSubscription) {
                    return;
                }

                $this->networkRepository->guardAgainstExceptionalMemberWhenLookingForOne(
                    function () use ($subscription) {
                        $this->accessor->subscribeToMemberTimeline($subscription);
                    },
                    $subscription->subscription->twitterId()
                );

                $this->memberSubscriptionRepository->saveMemberSubscription(
                    $member,
                    $subscription->subscription
                );
            }
        );
    }
}

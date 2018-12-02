<?php

namespace App\Member\Repository;

use App\Aggregate\Entity\MemberAggregateSubscription;
use App\Aggregate\Repository\MemberAggregateSubscriptionRepository;
use App\Member\Entity\AggregateSubscription;
use App\Member\Entity\MemberSubscription;
use App\Member\MemberInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;

class AggregateSubscriptionRepository extends EntityRepository
{
    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var MemberAggregateSubscriptionRepository
     */
    public $memberAggregateSubscriptionRepository;

    /**
     * @var MemberSubscriptionRepository
     */
    public $memberSubscriptionRepository;

    /**
     * @var NetworkRepository
     */
    public $networkRepository;

    /**
     * @param MemberAggregateSubscription $memberAggregateSubscription
     * @param MemberInterface             $subscription
     * @return AggregateSubscription
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @throws \Exception
     */
    public function findSubscriptionsByAggregateName(string $aggregateName)
    {
        $memberAggregateSubscription = $this->memberAggregateSubscriptionRepository
            ->findOneBy(['listName' => $aggregateName])
        ;

        if (!($memberAggregateSubscription instanceof MemberAggregateSubscription)) {
            throw new \Exception(
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function letMemberSubscribeToAggregate(string $memberName, string $aggregateName): void
    {
        $member = $this->accessor->ensureMemberHavingNameExists($memberName);

        try {
            $subscriptions = $this->findSubscriptionsByAggregateName($aggregateName);
        } catch (\Exception $exception) {
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
                    $subscription->subscription->getTwitterID()
                );

                $this->memberSubscriptionRepository->saveMemberSubscription(
                    $member,
                    $subscription->subscription
                );
            }
        );
    }
}

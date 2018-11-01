<?php

namespace App\Member\Repository;

use App\Aggregate\Entity\MemberAggregateSubscription;
use App\Member\Entity\AggregateSubscription;
use App\Member\MemberInterface;
use Doctrine\ORM\EntityRepository;

class AggregateSubscriptionRepository extends EntityRepository
{
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
}

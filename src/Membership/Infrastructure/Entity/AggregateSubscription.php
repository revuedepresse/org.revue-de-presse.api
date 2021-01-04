<?php

namespace App\Membership\Infrastructure\Entity;

use App\Twitter\Infrastructure\PublishersList\Entity\MemberAggregateSubscription;
use App\Membership\Domain\Model\MemberInterface;
use Ramsey\Uuid\UuidInterface;

class AggregateSubscription
{
    private UuidInterface $id;

    private MemberAggregateSubscription $memberAggregateSubscription;

    public function getMemberAggregateSubscription(): MemberAggregateSubscription {
        return $this->memberAggregateSubscription;
    }

    public MemberInterface $subscription;

    /**
     * @param MemberAggregateSubscription $memberAggregateSubscription
     * @param MemberInterface             $subscription
     */
    public function __construct(
        MemberAggregateSubscription $memberAggregateSubscription,
        MemberInterface $subscription
    ) {
        $this->memberAggregateSubscription = $memberAggregateSubscription;
        $this->subscription = $subscription;
    }
}

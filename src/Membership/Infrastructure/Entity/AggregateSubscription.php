<?php

namespace App\Membership\Infrastructure\Entity;

use App\Twitter\Infrastructure\PublishersList\Entity\MemberAggregateSubscription;

class AggregateSubscription
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var MemberAggregateSubscription
     */
    private $memberAggregateSubscription;

    /**
     * @return MemberAggregateSubscription
     */
    public function getMemberAggregateSubscription() {
        return $this->memberAggregateSubscription;
    }

    /**
     * @var MemberInterface
     */
    public $subscription;

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

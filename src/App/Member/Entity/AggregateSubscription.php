<?php

namespace App\Member\Entity;

use App\Aggregate\Entity\MemberAggregateSubscription;
use App\Member\MemberInterface;

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

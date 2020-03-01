<?php
declare(strict_types=1);

namespace App\Domain\Subscription;

use App\Aggregate\Entity\MemberAggregateSubscription;

interface MemberSubscriptionInterface
{
    /**
     * @return bool
     */
    public function isMemberAggregate(): bool;

    public function setMemberSubscription(
        MemberAggregateSubscription $memberAggregateSubscription
    ): self;
}
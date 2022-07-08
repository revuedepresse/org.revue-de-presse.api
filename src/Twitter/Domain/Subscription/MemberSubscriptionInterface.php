<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Subscription;

use App\Trends\Domain\Entity\MemberAggregateSubscription;

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

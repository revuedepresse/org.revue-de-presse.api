<?php

namespace App\Membership\Domain\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\PublishersList\Entity\MemberAggregateSubscription;

interface PublishersListSubscriptionRepositoryInterface
{
    public function make(
        MemberAggregateSubscription $memberAggregateSubscription,
        MemberInterface $subscription
    );
}
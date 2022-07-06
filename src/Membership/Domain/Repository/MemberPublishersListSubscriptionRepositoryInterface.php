<?php
declare (strict_types=1);

namespace App\Membership\Domain\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\PublishersList\Entity\MemberAggregateSubscription;

interface MemberPublishersListSubscriptionRepositoryInterface
{
    public function make(
        MemberInterface $member,
        array $list
    ): MemberAggregateSubscription;
}
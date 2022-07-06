<?php
declare (strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Subscription\Infrastructure\Entity\ListSubscription;

interface ListSubscriptionRepositoryInterface
{
    public function make(
        MemberInterface $member,
        array $list
    ): ListSubscription;
}
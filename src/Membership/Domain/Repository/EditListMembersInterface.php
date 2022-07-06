<?php

namespace App\Membership\Domain\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Subscription\Infrastructure\Entity\ListSubscription;

interface EditListMembersInterface
{
    public function make(
        ListSubscription $list,
        MemberInterface  $memberInList
    );
}
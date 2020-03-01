<?php
declare(strict_types=1);

namespace App\Member\Entity;

use App\Membership\Entity\MemberInterface;
use Ramsey\Uuid\UuidInterface;

class MemberSubscription
{
    private UuidInterface $id;

    private MemberInterface $member;

    private MemberInterface $subscription;

    /**
     * @param MemberInterface $member
     * @param MemberInterface $subscription
     */
    public function __construct(
        MemberInterface $member,
        MemberInterface $subscription
    ) {
        $this->member = $member;
        $this->subscription = $subscription;
    }
}

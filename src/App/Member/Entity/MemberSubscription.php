<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;

class MemberSubscription
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var MemberInterface
     */
    private $member;

    /**
     * @var MemberInterface
     */
    private $subscription;

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

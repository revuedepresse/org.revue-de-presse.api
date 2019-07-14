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
     * @var bool
     */
    private $hasBeenCancelled = false;

    /**
     * @param MemberInterface $member
     * @param MemberInterface $subscription
     * @param bool            $hasBeenCancelled
     */
    public function __construct(
        MemberInterface $member,
        MemberInterface $subscription,
        bool $hasBeenCancelled = false
    ) {
        $this->member = $member;
        $this->subscription = $subscription;
        $this->hasBeenCancelled = $hasBeenCancelled;
    }

    /**
     * @return MemberSubscription
     */
    public function markAsNotBeingCancelled(): self
    {
        $this->hasBeenCancelled = false;

        return $this;
    }
}

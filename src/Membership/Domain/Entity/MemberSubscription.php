<?php
declare(strict_types=1);

namespace App\Membership\Domain\Entity;

use Ramsey\Uuid\UuidInterface;

class MemberSubscription
{
    private UuidInterface $id;

    private MemberInterface $member;

    private MemberInterface $subscription;

    private bool $hasBeenCancelled = false;

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

    public function getSubscription(): MemberInterface
    {
        return $this->subscription;
    }

    public function markAsCancelled(): self
    {
        $this->hasBeenCancelled = true;

        return $this;
    }

    public function markAsNotBeingCancelled(): self
    {
        $this->hasBeenCancelled = false;

        return $this;
    }
}

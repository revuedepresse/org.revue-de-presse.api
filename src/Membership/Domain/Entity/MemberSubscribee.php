<?php

namespace App\Membership\Domain\Entity;

class MemberSubscribee
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
    private $subscribee;

    /**
     * @param MemberInterface $member
     * @param MemberInterface $subscribee
     */
    public function __construct(
        MemberInterface $member,
        MemberInterface $subscribee
    ) {
        $this->member = $member;
        $this->subscribee = $subscribee;
    }
}

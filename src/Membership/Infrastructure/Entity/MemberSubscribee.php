<?php

namespace App\Membership\Infrastructure\Entity;

use App\Membership\Domain\Model\MemberInterface;

class MemberSubscribee
{
    private $id;

    private $member;

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

<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;
use WTW\UserBundle\Entity\User;

class ProtectedMember implements MemberInterface
{
    use MemberTrait;

    /**
     * @param bool $protected
     * @return MemberInterface
     */
    public function setProtected(bool $protected): MemberInterface
    {
        return $this;
    }

    /**
     * @return boolean
     */
    public function isProtected(): bool
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function isNotProtected(): bool
    {
        return false;
    }

    /**
     * @param string $screenName
     * @return MemberInterface
     */
    public function make(string $screenName): MemberInterface
    {
        $member = new User();
        $member->setTwitterUsername($screenName);
        $member->setEmail('@'.$screenName);
        $member->setProtected(true);

        return $member;
    }
}

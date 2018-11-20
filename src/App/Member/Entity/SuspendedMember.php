<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;
use WTW\UserBundle\Entity\User;

class SuspendedMember implements MemberInterface
{
    use MemberTrait;
    use ExceptionalUserInterfaceTrait;

    /**
     * @param bool $protected
     * @return MemberInterface
     */
    public function setSuspended(bool $protected): MemberInterface
    {
        return $this;
    }

    /**
     * @return boolean
     */
    public function isSuspended(): bool
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function isNotSuspended(): bool
    {
        return false;
    }

    /**
     * @param string $screenName
     * @param int    $id
     * @return MemberInterface
     */
    public function make(string $screenName, int $id): MemberInterface
    {
        $member = new User();
        $member->setTwitterUsername($screenName);
        $member->setTwitterID($id);
        $member->setEmail('@'.$screenName);
        $member->setSuspended(true);

        return $member;
    }
}

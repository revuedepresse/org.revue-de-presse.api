<?php

namespace App\Member\Entity;

use App\Membership\Entity\MemberInterface;
use App\Membership\Entity\Member;

class ProtectedMember implements MemberInterface
{
    use MemberTrait;
    use ExceptionalUserInterfaceTrait;

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
     * @param int    $id
     * @return MemberInterface
     */
    public function make(string $screenName, int $id): MemberInterface
    {
        $member = new User();
        $member->setTwitterUsername($screenName);
        $member->setTwitterID($id);
        $member->setEmail('@'.$screenName);
        $member->setProtected(true);

        return $member;
    }
}

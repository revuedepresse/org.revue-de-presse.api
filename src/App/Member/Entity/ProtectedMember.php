<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;

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
}

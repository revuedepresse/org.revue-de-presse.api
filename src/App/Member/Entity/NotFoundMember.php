<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;

class NotFoundMember implements MemberInterface
{
    use MemberTrait;

    /**
     * @param $notFound
     * @return MemberInterface
     */
    public function setNotFound(bool $notFound): MemberInterface
    {
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasBeenDeclaredAsNotFound(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasNotBeenDeclaredAsNotFound(): bool
    {
        return false;
    }

}

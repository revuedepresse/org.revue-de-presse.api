<?php

namespace App\Membership\Domain\Entity;

use App\Membership\Domain\Entity\Legacy\Member;

class NotFoundMember implements TwitterMemberInterface
{
    use MemberTrait;
    use ExceptionalUserInterfaceTrait;

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

    public function make(string $screenName, int $id): MemberInterface
    {
        $member = new Member();
        $member->setScreenName($screenName);
        $member->setTwitterID((string) $id);
        $member->setEmail('@'.$screenName);
        $member->setNotFound(true);

        return $member;
    }
}

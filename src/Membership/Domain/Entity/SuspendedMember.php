<?php
declare(strict_types=1);

namespace App\Membership\Domain\Entity;

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
        $member = new Member();
        $member->setScreenName($screenName);
        $member->setTwitterID((string) $id);
        $member->setEmail('@'.$screenName);
        $member->setSuspended(true);

        return $member;
    }
}

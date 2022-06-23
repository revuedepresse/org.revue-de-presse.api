<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Entity;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Model\TwitterMemberInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;

class SuspendedMember implements TwitterMemberInterface
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
        $member->setTwitterScreenName($screenName);
        $member->setTwitterID((string) $id);
        $member->setEmail('@'.$screenName);
        $member->setSuspended(true);

        return $member;
    }
}

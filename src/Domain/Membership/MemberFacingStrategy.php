<?php
declare(strict_types=1);

namespace App\Domain\Membership;

use App\Amqp\SkippableMemberException;
use App\Membership\Entity\MemberInterface;
use App\Twitter\Api\Resource\MemberIdentity;
use function sprintf;

class MemberFacingStrategy
{
    /**
     * @param MemberInterface $member
     * @param MemberIdentity               $memberIdentity
     *
     * @throws SkippableMemberException
     */
    public static function guardAgainstProtectedMember(
        MemberInterface $member,
        MemberIdentity $memberIdentity
    ): void {
        if ($member->isProtected()) {
            throw new SkippableMemberException(
                sprintf(
                    'Ignoring protected member with screen name "%s"',
                    $memberIdentity->screenName()
                )
            );
        }
    }

    /**
     * @param MemberInterface $member
     * @param MemberIdentity               $memberIdentity
     *
     * @throws SkippableMemberException
     */
    public static function guardAgainstSuspendedMember(
        MemberInterface $member,
        MemberIdentity $memberIdentity
    ): void {
        if ($member->isSuspended()) {
            throw new SkippableMemberException(
                sprintf(
                    'Ignoring suspended member with screen name "%s"',
                    $memberIdentity->screenName()
                )
            );
        }
    }

}
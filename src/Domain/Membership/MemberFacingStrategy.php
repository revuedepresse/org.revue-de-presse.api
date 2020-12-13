<?php
declare(strict_types=1);

namespace App\Domain\Membership;

use App\Infrastructure\Amqp\Exception\SkippableMemberException;
use App\Membership\Domain\Entity\MemberInterface;
use App\Domain\Resource\MemberIdentity;
use Exception;
use function sprintf;

class MemberFacingStrategy implements MemberFacingStrategyInterface
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

    /**
     * @param $exception
     *
     * @return bool
     */
    public static function shouldBreakPublication(Exception $exception): bool
    {
        return $exception->getCode() === self::UNEXPECTED_ERROR
            || $exception->getCode() === self::UNAVAILABLE_RESOURCE;
    }

    /**
     * @param $exception
     *
     * @return bool
     */
    public static function shouldContinuePublication(Exception $exception): bool
    {
        return $exception->getCode() === self::NOT_FOUND_MEMBER
            || $exception->getCode() === self::SUSPENDED_USER
            || $exception->getCode() === self::PROTECTED_ACCOUNT;
    }
}
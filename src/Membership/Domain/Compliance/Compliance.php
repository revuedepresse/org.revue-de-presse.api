<?php
declare(strict_types=1);

namespace App\Domain\Membership\Compliance;

use App\Membership\Domain\Compliance\ApiComplianceInterface;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableMemberException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use Exception;
use function sprintf;

class Compliance implements ApiComplianceInterface
{
    /**
     * @param MemberInterface $member
     * @param MemberIdentity               $memberIdentity
     *
     * @throws SkippableMemberException
     */
    public static function skipProtectedMember(
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
    public static function skipSuspendedMember(
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
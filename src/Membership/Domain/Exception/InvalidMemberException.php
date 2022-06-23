<?php
declare(strict_types=1);

namespace App\Membership\Domain\Exception;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use function sprintf;

class InvalidMemberException extends \Exception
{
    /**
     * @throws \App\Twitter\Domain\Membership\Exception\InvalidMemberException
     */
    public static function guardAgainstInvalidUsername(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" could not be found.', $username)
        );
    }

    /**
     * @throws \App\Twitter\Domain\Membership\Exception\InvalidMemberException
     */
    public static function guardAgainstMemberDeclaredAsNotFound(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" could not be found.', $username)
        );
    }

    /**
     * @throws \App\Twitter\Domain\Membership\Exception\InvalidMemberException
     */
    public static function guardAgainstMemberDeclaredAsSuspended(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" is protected.', $username)
        );
    }

    /**
     * @throws \App\Twitter\Domain\Membership\Exception\InvalidMemberException
     */
    public static function guardAgainstMemberDeclaredAsProtected(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" is suspended.', $username)
        );
    }

    /**
     * @throws \App\Twitter\Domain\Membership\Exception\InvalidMemberException
     */
    public static function ensureMemberHavingUsernameIsAvailable(
        MemberRepositoryInterface $memberRepository,
        string $username
    ): MemberInterface {
        $member = $memberRepository->findOneBy(['twitter_username' => $username]);

        if (!($member instanceof MemberInterface)) {
            self::guardAgainstInvalidUsername($username);
        }

        if ($member->hasBeenDeclaredAsNotFound()) {
            self::guardAgainstMemberDeclaredAsNotFound($username);
        }

        if ($member->isProtected()) {
            self::guardAgainstMemberDeclaredAsProtected($username);
        }

        if ($member->isSuspended()) {
            self::guardAgainstMemberDeclaredAsSuspended($username);
        }

        return $member;
    }
}

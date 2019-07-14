<?php

namespace App\Member\Exception;

use App\Member\MemberInterface;
use WTW\UserBundle\Repository\UserRepository;

class InvalidMemberException extends \Exception
{
    /**
     * @param $username
     * @throws InvalidMemberException
     */
    public static function guardAgainstInvalidUsername(string $username)
    {
        throw new self(
            sprintf('Member with username "%s" could not be found.', $username)
        );
    }

    /**
     * @param string $username
     * @throws InvalidMemberException
     */
    public static function guardAgainstMemberDeclaredAsNotFound(string $username)
    {
        throw new self(
            sprintf('Member with username "%s" could not be found.', $username)
        );
    }

    /**
     * @param string $username
     * @throws InvalidMemberException
     */
    public static function guardAgainstMemberDeclaredAsSuspended(string $username)
    {
        throw new self(
            sprintf('Member with username "%s" is protected.', $username)
        );
    }

    /**
     * @param string $username
     * @throws InvalidMemberException
     */
    public static function guardAgainstMemberDeclaredAsProtected(string $username)
    {
        throw new self(
            sprintf('Member with username "%s" is suspended.', $username)
        );
    }

    /**
     * @param UserRepository $memberRepository
     * @param string         $username
     * @return MemberInterface|null
     */
    public static function ensureMemberHavingUsernameIsAvailable(
        UserRepository $memberRepository,
        string $username
    ): ?MemberInterface {
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

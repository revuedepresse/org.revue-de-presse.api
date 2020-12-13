<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Membership\Exception;

use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use function sprintf;

class InvalidMemberException extends \Exception
{
    public static function guardAgainstInvalidUsername(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" could not be found.', $username)
        );
    }

    public static function guardAgainstMemberDeclaredAsNotFound(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" could not be found.', $username)
        );
    }

    public static function guardAgainstMemberDeclaredAsSuspended(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" is protected.', $username)
        );
    }

    public static function guardAgainstMemberDeclaredAsProtected(string $username): void
    {
        throw new self(
            sprintf('Member with username "%s" is suspended.', $username)
        );
    }

    /**
     * @param MemberRepository $memberRepository
     * @param string           $username
     *
     * @return MemberInterface
     * @throws InvalidMemberException
     */
    public static function ensureMemberHavingUsernameIsAvailable(
        MemberRepository $memberRepository,
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

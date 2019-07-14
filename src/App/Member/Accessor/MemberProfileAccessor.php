<?php

namespace App\Member\Accessor;

use App\Member\Exception\InvalidMemberException;
use App\Member\MemberInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WTW\UserBundle\Repository\UserRepository;

class MemberProfileAccessor
{
    /**
     * @var Accessor
     */
    private $accessor;
    /**
     * @var UserRepository
     */
    private $userManager;

    /**
     * @param Accessor       $accessor
     * @param UserRepository $userManager
     */
    public function __construct(Accessor $accessor, UserRepository $userManager)
    {
        $this->accessor = $accessor;
        $this->userManager = $userManager;
    }

    /**
     * @param string $username
     * @return MemberInterface
     * @throws InvalidMemberException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function refresh(string $username): MemberInterface
    {
        $fetchedMember = $this->accessor->showUser($username);
        $member = $this->userManager->findOneBy(['twitterID' => $fetchedMember->id]);
        if ($member instanceof MemberInterface) {
            $this->ensureMemberProfileIsUpToDate($member, $username, $fetchedMember);

            return $member;
        }

        InvalidMemberException::guardAgainstInvalidUsername($username);
    }

    /**
     * @param MemberInterface $member
     * @param string          $memberName
     * @param \stdClass|null  $remoteMember
     * @return MemberInterface
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function ensureMemberProfileIsUpToDate(
        MemberInterface $member,
        string $memberName,
        \stdClass $remoteMember = null
    ): MemberInterface {
        $memberBioIsAvailable = $member->isNotSuspended() &&
            $member->isNotProtected() &&
            $member->hasNotBeenDeclaredAsNotFound()
        ;

        if (!$memberBioIsAvailable) {
            return $member;
        }

        if (is_null($remoteMember)) {
            $remoteMember = $this->accessor->showUser($memberName);
        }

        $member->description = $remoteMember->description;
        $member->url = $remoteMember->url;

        return $this->userManager->saveMember($member);
    }
}

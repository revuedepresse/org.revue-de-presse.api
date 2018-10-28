<?php

namespace WTW\UserBundle\Repository;

use App\Member\MemberInterface;
use Doctrine\ORM\EntityRepository;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WTW\UserBundle\Entity\User;

/**
 * @package WTW\UserBundle\Repository
 */
class UserRepository extends EntityRepository
{
    /**
     * @param      $twitterId
     * @param      $screenName
     * @param bool $protected
     * @param bool $suspended
     * @param null $description
     * @param int  $totalSubscriptions
     * @param int  $totalSubscribees
     * @return User
     */
    public function make(
        $twitterId,
        $screenName,
        $protected = false,
        $suspended = false,
        $description = null,
        $totalSubscriptions = 0,
        $totalSubscribees = 0
    ) {
        $member = new User();
        $member->setTwitterUsername($screenName);
        $member->setTwitterID($twitterId);
        $member->setEnabled(false);
        $member->setLocked(false);
        $member->setEmail('@' . $screenName);
        $member->setEnabled(0);
        $member->setProtected($protected);
        $member->setSuspended($suspended);

        if (!is_null($description)) {
            $member->description = $description;
        }

        $member->totalSubscribees = $totalSubscribees;
        $member->totalSubscriptions = $totalSubscriptions;

        return $member;
    }

    /**
     * @param string $screenName
     * @return null|object|User
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function suspendMember(string $screenName)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);

        if ($member instanceof User) {
            $member->setSuspended(true);

            return $this->saveUser($member);
        }

        $member = new User();
        $member->setTwitterUsername($screenName);
        $member->setTwitterID(0);
        $member->setEnabled(false);
        $member->setLocked(false);
        $member->setEmail('@' . $screenName);
        $member->setEnabled(0);
        $member->setProtected(false);
        $member->setSuspended(true);

        return $this->saveUser($member);
    }

    /**
     * @param $screenName
     * @return MemberInterface|null
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareUserAsNotFoundByUsername($screenName)
    {
        $user = $this->findOneBy(['twitter_username' => $screenName]);

        if (!$user instanceof User) {
            return null;
        }

        return $this->declareUserAsNotFound($user);
    }

    /**
     * @param User $user
     * @return MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareUserAsNotFound(User $user)
    {
        $user->setNotFound(true);

        return $this->saveUser($user);
    }

    /**
     * @param User $user
     * @return MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareUserAsFound(User $user)
    {
        $user->setNotFound(false);

        return $this->saveUser($user);
    }

    /**
     * @param string $screenName
     * @return MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareUserAsProtected(string $screenName)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof User) {
            return $this->make(
                0,
                $screenName,
                $protected = true
            );
        }

        $member->setProtected(true);

        return $this->saveMember($member);
    }

    /**
     * @param MemberInterface $member
     * @return MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveMember(MemberInterface $member)
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($member);
        $entityManager->flush();

        return $member;
    }

    /**
     * @param User $member
     * @return MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function saveUser(User $member)
    {
        return $this->saveMember($member);
    }

    /**
     * @param string $maxStatusId
     * @param string $screenName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareMaxStatusIdForMemberWithScreenName(string $maxStatusId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->maxStatusId) || (intval($maxStatusId) > intval($member->maxStatusId))) {
            $member->maxStatusId = $maxStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $minStatusId
     * @param string $screenName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareMinStatusIdForMemberWithScreenName(string $minStatusId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->minStatusId) || (intval($minStatusId) < intval($member->minStatusId))) {
            $member->minStatusId = $minStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $maxLikeId
     * @param string $screenName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareMaxLikeIdForMemberWithScreenName(string $maxLikeId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->maxLikeId) || (intval($maxLikeId) > intval($member->maxLikeId))) {
            $member->maxLikeId = $maxLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $minLikeId
     * @param string $screenName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareMinLikeIdForMemberWithScreenName(string $minLikeId, string $screenName)
    {
        $member = $this->ensureMemberExists($screenName);

        if (is_null($member->minLikeId) || (intval($minLikeId) < intval($member->minLikeId))) {
            $member->minLikeId = $minLikeId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param int    $totalStatuses
     * @param string $screenName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareTotalStatusesOfMemberWithName(int $totalStatuses, string $screenName) {
        $member = $this->ensureMemberExists($screenName);

        if ($totalStatuses > $member->totalStatuses) {
            $member->totalStatuses = $totalStatuses;

            $this->saveMember($member);
        }

        return $member;
    }

    /**
     * @param int    $totalLikes
     * @param string $memberName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareTotalLikesOfMemberWithName(int $totalLikes, string $memberName) {
        $member = $this->ensureMemberExists($memberName);

        if ($totalLikes > $member->totalLikes) {
            $member->totalLikes = $totalLikes;

            $this->saveMember($member);
        }

        return $member;
    }

    /**
     * @param int    $statusesToBeAdded
     * @param string $screenName
     * @return null|object
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function incrementTotalStatusesOfMemberWithName(
        int $statusesToBeAdded,
        string $memberName
    ) {
        $member = $this->ensureMemberExists($memberName);

        $member->totalStatuses = $member->totalStatuses + $statusesToBeAdded;
        $this->saveMember($member);

        return $member;
    }

    /**
     * @param int    $likesToBeAdded
     * @param string $memberName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function incrementTotalLikesOfMemberWithName(
        int $likesToBeAdded,
        string $memberName
    ) {
        $member = $this->ensureMemberExists($memberName);

        $member->totalLikes = $member->totalLikes + $likesToBeAdded;
        $this->saveMember($member);

        return $member;
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMemberHavingApiKey()
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->andWhere('u.apiKey is not null');

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param string $memberName
     * @return MemberInterface
     * @throws NotFoundMemberException
     */
    private function ensureMemberExists(string $memberName)
    {
        $member = $this->findOneBy(['twitter_username' => $memberName]);
        if (!$member instanceof MemberInterface) {
            NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName($memberName);
        }

        return $member;
    }
}

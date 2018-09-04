<?php

namespace WTW\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WTW\UserBundle\Entity\User;

/**
 * @package WTW\UserBundle\Repository
 */
class UserRepository extends EntityRepository
{
    /**
     * @param $twitterId
     * @param $screenName
     * @param bool|false $protected
     * @param bool|false $suspended
     * @return User
     */
    public function make($twitterId, $screenName, $protected = false, $suspended = false)
    {
        $user = new User();
        $user->setTwitterUsername($screenName);
        $user->setTwitterID($twitterId);
        $user->setEnabled(false);
        $user->setLocked(false);
        $user->setEmail('@' . $screenName);
        $user->setEnabled(0);
        $user->setProtected($protected);
        $user->setSuspended($suspended);

        return $user;
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
     * @return null|User
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
     * @return User
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareUserAsNotFound(User $user)
    {
        $user->setNotFound(true);

        return $this->saveUser($user);
    }

    /**
     * @param User $user
     * @return User
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareUserAsFound(User $user)
    {
        $user->setNotFound(false);

        return $this->saveUser($user);
    }

    /**
     * @param $screenName
     * @return User
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
     * @param User $member
     * @return User
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function saveMember(User $member)
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($member);
        $entityManager->flush();

        return $member;
    }

    /**
     * @param User $member
     * @return User
     * @throws \Doctrine\ORM\OptimisticLockException
     * @deprecated in favor of ->saveMember
     */
    protected function saveUser(User $member)
    {
        return $this->saveMember($member);
    }

    /**
     * @param string $maxStatusId
     * @param string $screenName
     * @return User
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareMaxStatusIdForMemberWithScreenName(string $maxStatusId, string $screenName)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof User) {
            throw new NotFoundMemberException(
                'Could not find member with screen name "%s"',
                $screenName
            );
        }

        if (is_null($member->maxStatusId) || (intval($maxStatusId) > intval($member->maxStatusId))) {
            $member->maxStatusId = $maxStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param string $minStatusId
     * @param string $screenName
     * @return User
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareMinStatusIdForMemberWithScreenName(string $minStatusId, string $screenName)
    {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof User) {
            throw new NotFoundMemberException(
                'Could not find member with screen name "%s"',
                $screenName
            );
        }

        if (is_null($member->minStatusId) || (intval($minStatusId) < intval($member->minStatusId))) {
            $member->minStatusId = $minStatusId;
        }

        return $this->saveMember($member);
    }

    /**
     * @param int    $totalStatuses
     * @param string $screenName
     * @return null|object
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareTotalStatusesOfMemberWithScreenName(int $totalStatuses, string $screenName) {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof User) {
            throw new NotFoundMemberException(
                'Could not find member with screen name "%s"',
                $screenName
            );
        }

        if ($totalStatuses > $member->totalStatuses) {
            $member->totalStatuses = $totalStatuses;

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
    public function incrementTotalStatusesOfMemberWithScreenName(int $statusesToBeAdded, string $screenName) {
        $member = $this->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof User) {
            NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName($screenName);
        }

        $member->totalStatuses = $member->totalStatuses + $statusesToBeAdded;
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
}

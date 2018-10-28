<?php

namespace App\Member\Repository;

use App\Member\Entity\MemberSubscription;
use App\Member\MemberInterface;
use Doctrine\ORM\EntityRepository;
use WTW\UserBundle\Repository\UserRepository;

class MemberSubscriptionRepository extends EntityRepository
{
    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @param MemberInterface $member
     * @param MemberInterface $subscription
     * @return MemberSubscription
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveMemberSubscription(
        MemberInterface $member,
        MemberInterface $subscription
    ) {
        $memberSubscription = $this->findOneBy(['member' => $member, 'subscription' => $subscription]);

        if (!($memberSubscription instanceof MemberSubscription)) {
            $memberSubscription = new MemberSubscription($member, $subscription);
        }

        $this->getEntityManager()->persist($memberSubscription);
        $this->getEntityManager()->flush();

        return $memberSubscription;
    }
}

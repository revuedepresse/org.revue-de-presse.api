<?php

namespace App\Member\Repository;

use App\Member\Entity\MemberSubscribee;
use App\Member\MemberInterface;
use Doctrine\ORM\EntityRepository;
use WTW\UserBundle\Repository\UserRepository;

class MemberSubscribeeRepository extends EntityRepository
{
    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @param MemberInterface $member
     * @param MemberInterface $subscribee
     * @return MemberSubscribee
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveMemberSubscribee(
        MemberInterface $member,
        MemberInterface $subscribee
    ) {
        $memberSubscribee = $this->findOneBy(['member' => $member, 'subscribee' => $subscribee]);

        if (!($memberSubscribee instanceof MemberSubscribee)) {
            $memberSubscribee = new MemberSubscribee($member, $subscribee);
        }

        $this->getEntityManager()->persist($memberSubscribee);
        $this->getEntityManager()->flush();

        return $memberSubscribee;
    }
}

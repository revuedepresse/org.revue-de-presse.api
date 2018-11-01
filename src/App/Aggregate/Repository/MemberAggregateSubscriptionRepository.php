<?php

namespace App\Aggregate\Repository;

use App\Aggregate\Entity\MemberAggregateSubscription;
use App\Member\MemberInterface;
use Doctrine\ORM\EntityRepository;

class MemberAggregateSubscriptionRepository extends EntityRepository
{
    /**
     * @param MemberInterface $member
     * @param array           $list
     * @return MemberAggregateSubscription
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function make(
        MemberInterface $member,
        array $list
    ) {
        if (!array_key_exists('name', $list) ||
            !array_key_exists('id', $list)) {
            throw new \LogicException(
                'A list should have a "name" property and an "id" property'
            );
        }

        $memberAggregateSubscription = $this->findOneBy([
            'listName' => $list['name'],
            'listId' => $list['id'],
            'member' => $member
        ]);

        if (!($memberAggregateSubscription instanceof MemberAggregateSubscription)) {
            $memberAggregateSubscription = new MemberAggregateSubscription($member, $list);
        }

        return $this->saveMemberAggregateSubscription($memberAggregateSubscription);
    }

    /**
     * @param MemberAggregateSubscription $memberAggregateSubscription
     * @return MemberAggregateSubscription
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveMemberAggregateSubscription(MemberAggregateSubscription $memberAggregateSubscription)
    {
        $this->getEntityManager()->persist($memberAggregateSubscription);
        $this->getEntityManager()->flush();

        return $memberAggregateSubscription;
    }
}

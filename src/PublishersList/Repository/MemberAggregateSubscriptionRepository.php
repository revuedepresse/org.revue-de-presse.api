<?php

namespace App\PublishersList\Repository;

use App\PublishersList\Entity\MemberAggregateSubscription;
use App\Membership\Domain\Entity\MemberInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use const JSON_THROW_ON_ERROR;

class MemberAggregateSubscriptionRepository extends EntityRepository
{
    /**
     * @param MemberInterface $member
     * @param array           $list
     *
     * @return MemberAggregateSubscription
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function make(
        MemberInterface $member,
        array $list
    ): MemberAggregateSubscription {
        if (!\array_key_exists('name', $list) ||
            !\array_key_exists('id', $list)) {
            throw new \LogicException(
                'A list should have a "name" property and an "id" property'
            );
        }

        $memberAggregateSubscription = $this->findOneBy([
            'listName' => $list['name'],
            'listId' => $list['id'],
            'member' => $member
        ]);

        if ($memberAggregateSubscription instanceof MemberAggregateSubscription) {
            $memberAggregateSubscription->setDocument(
                json_encode($list, JSON_THROW_ON_ERROR)
            );
        }

        if (!($memberAggregateSubscription instanceof MemberAggregateSubscription)) {
            $memberAggregateSubscription = new MemberAggregateSubscription($member, $list);
        }

        return $this->saveMemberAggregateSubscription($memberAggregateSubscription);
    }

    /**
     * @param MemberAggregateSubscription $memberAggregateSubscription
     *
     * @return MemberAggregateSubscription
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function saveMemberAggregateSubscription(
        MemberAggregateSubscription $memberAggregateSubscription
    ): MemberAggregateSubscription {
        $this->getEntityManager()->persist($memberAggregateSubscription);
        $this->getEntityManager()->flush();

        return $memberAggregateSubscription;
    }
}

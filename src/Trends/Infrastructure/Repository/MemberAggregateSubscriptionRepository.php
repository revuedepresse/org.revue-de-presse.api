<?php

namespace App\Trends\Infrastructure\Repository;

use App\Membership\Domain\Entity\MemberInterface;
use App\Trends\Domain\Entity\MemberAggregateSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use const JSON_THROW_ON_ERROR;

class MemberAggregateSubscriptionRepository extends ServiceEntityRepository
{
    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
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

    public function saveMemberAggregateSubscription(
        MemberAggregateSubscription $memberAggregateSubscription
    ): MemberAggregateSubscription {
        $this->getEntityManager()->persist($memberAggregateSubscription);
        $this->getEntityManager()->flush();

        return $memberAggregateSubscription;
    }
}

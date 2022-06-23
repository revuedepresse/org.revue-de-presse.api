<?php

namespace App\Twitter\Infrastructure\PublishersList\Repository;

use App\Membership\Domain\Repository\MemberPublishersListSubscriptionRepositoryInterface;
use App\Twitter\Infrastructure\PublishersList\Entity\MemberAggregateSubscription;
use App\Membership\Domain\Model\MemberInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use const JSON_THROW_ON_ERROR;

class MemberAggregateSubscriptionRepository extends ServiceEntityRepository implements MemberPublishersListSubscriptionRepositoryInterface
{
    public const TABLE_ALIAS = 'member_aggregate_subscription';

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

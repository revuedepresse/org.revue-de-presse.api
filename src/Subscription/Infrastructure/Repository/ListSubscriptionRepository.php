<?php

namespace App\Subscription\Infrastructure\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Subscription\Domain\Repository\ListSubscriptionRepositoryInterface;
use App\Subscription\Infrastructure\Entity\ListSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use const JSON_THROW_ON_ERROR;

class ListSubscriptionRepository extends ServiceEntityRepository implements ListSubscriptionRepositoryInterface
{
    public const TABLE_ALIAS = 'member_aggregate_subscription';

    /**
     * @throws \JsonException
     */
    public function make(
        MemberInterface $member,
        array $list
    ): ListSubscription {
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

        if ($memberAggregateSubscription instanceof ListSubscription) {
            $memberAggregateSubscription->setDocument(
                json_encode($list, JSON_THROW_ON_ERROR)
            );
        }

        if (!($memberAggregateSubscription instanceof ListSubscription)) {
            $memberAggregateSubscription = new ListSubscription($member, $list);
        }

        return $this->saveMemberAggregateSubscription($memberAggregateSubscription);
    }

    public function saveMemberAggregateSubscription(
        ListSubscription $memberAggregateSubscription
    ): ListSubscription {
        $this->getEntityManager()->persist($memberAggregateSubscription);
        $this->getEntityManager()->flush();

        return $memberAggregateSubscription;
    }
}

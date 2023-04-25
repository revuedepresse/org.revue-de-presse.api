<?php

namespace App\Subscription\Infrastructure\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Subscription\Domain\Repository\ListSubscriptionRepositoryInterface;
use App\Subscription\Infrastructure\Entity\ListSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use JsonException;
use const JSON_THROW_ON_ERROR;

class ListSubscriptionRepository extends ServiceEntityRepository implements ListSubscriptionRepositoryInterface
{
    public const TABLE_ALIAS = 'member_aggregate_subscription';

    /**
     * @throws JsonException
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

        $twitterList = $this->findOneBy([
            'listId' => $list['id'],
            'member' => $member
        ]);

        if ($twitterList instanceof ListSubscription) {
            $twitterList->setDocument(
                json_encode(
                    $list,
                    JSON_THROW_ON_ERROR
                )
            );
            $twitterList->setName($list['name']);
        }

        if (!($twitterList instanceof ListSubscription)) {
            $twitterList = new ListSubscription($member, $list);
        }

        return $this->saveMemberAggregateSubscription($twitterList);
    }

    public function saveMemberAggregateSubscription(
        ListSubscription $twitterList
    ): ListSubscription {
        $this->getEntityManager()->persist($twitterList);
        $this->getEntityManager()->flush();

        return $twitterList;
    }
}

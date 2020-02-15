<?php

namespace App\Member\Repository;

use App\Member\Entity\MemberSubscription;
use App\Membership\Entity\MemberInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use App\Member\Repository\MemberRepository;

class MemberSubscriptionRepository extends ServiceEntityRepository
{
    /**
     * @var MemberRepository
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

    public function findMissingSubscriptions(MemberInterface $member, array $subscriptions)
    {
        $query = <<< QUERY
            SELECT GROUP_CONCAT(sm.usr_twitter_id) subscription_ids
            FROM member_subscription s,
            weaving_user sm
            WHERE sm.usr_id = s.subscription_id
            AND member_id = :member_id
            AND sm.usr_twitter_id is not null
            AND sm.usr_twitter_id in (:subscription_ids)
QUERY;

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->executeQuery(
            strtr(
                $query,
                [
                    ':member_id' => $member->getId(),
                    ':subscription_ids' => (string) implode(',', $subscriptions)
                ]
            )
        );

        $results = $statement->fetchAll();

        $remainingSubscriptions = $subscriptions;
        if (array_key_exists(0, $results) && array_key_exists('subscription_ids', $results[0])) {
            $subscriptionIds = array_map(
                'intval',
                explode(',', $results[0]['subscription_ids'])
            );
            $remainingSubscriptions = array_diff(
                array_values($subscriptions),
                $subscriptionIds
            );
        }

        return $remainingSubscriptions;
    }
}

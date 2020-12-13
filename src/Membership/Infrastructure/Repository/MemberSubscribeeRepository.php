<?php

namespace App\Membership\Infrastructure\Repository;

use App\Membership\Domain\Entity\MemberSubscribee;
use App\Membership\Domain\Entity\MemberInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use Doctrine\Persistence\ManagerRegistry;

class MemberSubscribeeRepository extends ServiceEntityRepository
{
    /**
     * @var MemberRepository
     */
    public $memberRepository;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string         $aggregate
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        string $aggregateClass
    )
    {
        parent::__construct($managerRegistry, $aggregateClass);
    }

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

    /**
     * @param MemberInterface $member
     * @param array           $subscribees
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findMissingSubscribees(MemberInterface $member, array $subscribees)
    {
        $query = <<< QUERY
            SELECT GROUP_CONCAT(sm.usr_twitter_id) subscribee_ids
            FROM member_subscribee s,
            weaving_user sm
            WHERE sm.usr_id = s.subscribee_id
            AND member_id = :member_id
            AND sm.usr_twitter_id is not null
            AND sm.usr_twitter_id in (:subscribee_ids)
QUERY;

        $connection = $this->getEntityManager()->getConnection();
        $statement = $connection->executeQuery(
            strtr(
                $query,
                [
                    ':member_id' => $member->getId(),
                    ':subscribee_ids' => (string) implode(',', $subscribees)
                ]
            )
        );

        $results = $statement->fetchAll();

        $remainingSubscribees = $subscribees;
        if (array_key_exists(0, $results) && array_key_exists('subscribee_ids', $results[0])) {
            $subscribeeIds = array_map(
                'intval',
                explode(',', $results[0]['subscribee_ids'])
            );
            $remainingSubscribees = array_diff(
                array_values($subscribees),
                $subscribeeIds
            );
        }

        return $remainingSubscribees;
    }
}

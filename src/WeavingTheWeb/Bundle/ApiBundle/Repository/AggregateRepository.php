<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;

/**
 * @package WeavingTheWeb\Bundle\ApiBundle\Repository
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AggregateRepository extends ResourceRepository
{
    /**
     * @param $screenName
     * @param $listName
     * @return Aggregate
     */
    public function make($screenName, $listName)
    {
        return new Aggregate($screenName, $listName);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function selectAggregatesForWhichNoStatusHasBeenCollected(): array
    {
        $selectAggregates = <<<QUERY
            SELECT 
            a.id aggregate_id, 
            screen_name member_screen_name, 
            `name` aggregate_name,
            u.usr_twitter_id member_id
            FROM weaving_aggregate a, weaving_user u
            WHERE screen_name IS NOT NULL 
            AND a.screen_name = u.usr_twitter_username
            AND id NOT IN (
                SELECT aggregate_id FROM weaving_status_aggregate
            );
QUERY;

        $statement = $this->getEntityManager()->getConnection()->executeQuery($selectAggregates);

        return $statement->fetchAll();
    }

    /**
     * @param Aggregate $aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function lockAggregate(Aggregate $aggregate)
    {
        $aggregate->lock();

        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Aggregate $aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unlockAggregate(Aggregate $aggregate)
    {
        $aggregate->unlock();

        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Aggregate $aggregate
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Aggregate $aggregate)
    {
        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();
    }
}

<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\ORM\NoResultException;
use FOS\ElasticaBundle\Doctrine\ORM\Provider;
use Symfony\Component\Validator\Constraints\DateTime;
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

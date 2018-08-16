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
    public function make($screenName, $listName)
    {
        return new Aggregate($screenName, $listName);
    }

    public function lockAggregate(Aggregate $aggregate)
    {
        $aggregate->lock();

        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();
    }

    public function unlockAggregate(Aggregate $aggregate)
    {
        $aggregate->unlock();

        $this->getEntityManager()->persist($aggregate);
        $this->getEntityManager()->flush();
    }
}

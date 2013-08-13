<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Repository;

use Doctrine\ORM\EntityRepository;
use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective;

/**
 * Class PerspectiveRepository
 *
 * @package WTW\API\DataMiningBundle\Repository
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PerspectiveRepository extends EntityRepository
{
    public function savePerspective($sql)
    {
        $perspective = new Perspective();
        $perspective->setValue($sql);
        $perspective->setType(1);
        $perspective->setStatus(1);
        $perspective->setCreationDate(new \DateTime());

        return $perspective;
    }

    /**
     * @param $hash
     * @return mixed
     */
    public function findOneByPartialHash($hash)
    {
        $paddedHash = str_pad(substr($hash, 0, 7), 7, '-', STR_PAD_RIGHT) . substr($hash, 7);

        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->andWhere('p.hash LIKE :hash');
        $queryBuilder->setParameter('hash', $paddedHash . '%');

        return $queryBuilder->getQuery()->getSingleResult();
    }
}

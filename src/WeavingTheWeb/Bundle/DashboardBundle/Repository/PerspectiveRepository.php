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
    /**
     * @param $sql
     * @param \ArrayAccess $setters
     * @return Perspective
     */
    public function savePerspective($sql, \ArrayAccess $setters = null)
    {
        $perspective = new Perspective();
        $perspective->setValue($sql);
        $perspective->setType(1);
        $perspective->setStatus(1);
        $perspective->setCreationDate(new \DateTime());

        foreach ($setters as $setter) {
            $perspective = $setter($perspective);
        }

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

    /**
     * @param array $columns
     * @param array $conditions
     * @param array $parameters
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function getIterablePerspectives(array $columns = [], array $conditions = [], array $parameters = [])
    {
        $perspectiveAlias = 'p';

        /**
         * @var $queryBuilder \Doctrine\ORM\QueryBuilder
         */
        $queryBuilder = $this->createQueryBuilder($perspectiveAlias);

        foreach ($conditions as $condition) {
            $queryBuilder->andWhere(str_replace('{alias}', $perspectiveAlias, $condition));
        }
        if (!empty($columns)) {
            $queryBuilder->select(str_replace('{alias}', $perspectiveAlias, $columns));
        }
        foreach ($parameters as $name => $value) {
            $queryBuilder->setParameter($name, $value);
        }

        $query = $queryBuilder->getQuery();

        return $query->iterate();
    }
}

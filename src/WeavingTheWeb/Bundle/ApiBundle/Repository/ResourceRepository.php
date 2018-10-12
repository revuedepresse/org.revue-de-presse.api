<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityRepository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WeavingTheWeb\Bundle\ApiBundle\Repository
 */
abstract class ResourceRepository extends EntityRepository
{
    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\ORM\QueryFactory
     */
    public $queryFactory;

    public function getSelectQueryBuilder(array $constraints = [])
    {
        $this->createQueryBuilder($this->getAlias());
        $entityName = $this->getEntityName();

        return $this->queryFactory->querySelection($entityName, $constraints);
    }
}

<?php

namespace App\Api\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityRepository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package App\Api\Repository
 */
abstract class ResourceRepository extends ServiceEntityRepository
{
    /**
     * @var \App\Api\ORM\QueryFactory
     */
    public $queryFactory;

    public function getSelectQueryBuilder(array $constraints = [])
    {
        $this->createQueryBuilder($this->getAlias());
        $entityName = $this->getEntityName();

        return $this->queryFactory->querySelection($entityName, $constraints);
    }
}

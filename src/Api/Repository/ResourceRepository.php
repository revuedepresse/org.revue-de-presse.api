<?php

namespace App\Api\Repository;

use App\Api\ORM\QueryFactory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package App\Api\Repository
 */
abstract class ResourceRepository extends ServiceEntityRepository
{
    /**
     * @var QueryFactory
     */
    public QueryFactory $queryFactory;

    public function getSelectQueryBuilder(array $constraints = [])
    {
        $this->createQueryBuilder($this->getAlias());
        $entityName = $this->getEntityName();

        return $this->queryFactory->querySelection($entityName, $constraints);
    }
}

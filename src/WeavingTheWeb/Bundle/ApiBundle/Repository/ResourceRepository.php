<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\EntityRepository;

/**
 * Class ResourceRepository
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WeavingTheWeb\Bundle\ApiBundle\Repository
 */
abstract class ResourceRepository extends EntityRepository
{
    /**
     * @var \WTW\API\DataMiningBundle\ORM\QueryFactory
     */
    protected $queryFactory;

    /**
     *  @DI\InjectParams({
     *     "queryFactory" = @DI\Inject("weaving_the_web.api.query_factory")
     * })
     */
    public function setQueryFactory($queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function getSelectQueryBuilder(array $constraints = [])
    {
        $this->createQueryBuilder($this->getAlias());
        $entityName = $this->getEntityName();

        return $this->queryFactory->querySelection($entityName, $constraints);
    }
}

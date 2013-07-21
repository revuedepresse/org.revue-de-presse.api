<?php

namespace WeavingTheWeb\Bundle\ApiBundle;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class DataProvider
{
    protected $queryFactory;

    public function __construct($queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function getByConstraints($repository, array $constraints = array())
    {
        $queryBuilder = $this->queryFactory->querySelection($repository, $constraints);

        return $queryBuilder->getQuery()->getResult();
    }
}
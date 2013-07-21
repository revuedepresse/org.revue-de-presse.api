<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class JsonRepository extends ResourceRepository
{
    public function getAlias()
    {
        return 'jsn';
    }
}
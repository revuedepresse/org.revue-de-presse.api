<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStreamRepository extends ResourceRepository
{
    public function getAlias()
    {
        return 'ust';
    }
}
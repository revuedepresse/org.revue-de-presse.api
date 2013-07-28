<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class GithubRepository extends ResourceRepository
{
    public function getAlias()
    {
        return 'rep';
    }
}

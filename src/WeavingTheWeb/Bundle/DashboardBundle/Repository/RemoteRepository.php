<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Repository;

use Doctrine\ORM\EntityRepository;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Remote;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class RemoteRepository extends EntityRepository
{

    public function make($host, $accessToken)
    {
        return new Remote($host, $accessToken);
    }
}

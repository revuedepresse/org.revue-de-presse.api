<?php

namespace WTW\DashboardBundle\Repository;

use Doctrine\ORM\EntityRepository;
use WTW\DashboardBundle\Entity\Perspective;

/**
 * Class PerspectiveRepository
 *
 * @package WTW\API\DataMiningBundle\Repository
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PerspectiveRepository extends EntityRepository
{
    public function savePerspective($sql)
    {
        $perspective = new Perspective();
        $perspective->setValue($sql);
        $perspective->setType(1);
        $perspective->setStatus(1);
        $perspective->setCreationDate(new \DateTime());

        return $perspective;
    }
}
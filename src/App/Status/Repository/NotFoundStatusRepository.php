<?php

namespace App\Status\Repository;

use App\Status\Entity\NotFoundStatus;
use Doctrine\ORM\EntityRepository;
use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

class NotFoundStatusRepository extends EntityRepository
{
    /**
     * @param StatusInterface $status
     * @return NotFoundStatus
     */
    public function markStatusAsNotFound(StatusInterface $status)
    {
        if ($status instanceof ArchivedStatus) {
            return new NotFoundStatus(null, $status);
        }

        if ($status instanceof Status) {
            return new NotFoundStatus($status);
        }

        throw new \LogicException('A valid input status should be declared as not found');
    }
}

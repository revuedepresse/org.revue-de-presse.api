<?php

namespace App\Status\Repository;

use App\Status\Entity\NotFoundStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Entity\StatusInterface;
use Laminas\Service;

class NotFoundStatusRepository extends ServiceEntityRepository
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

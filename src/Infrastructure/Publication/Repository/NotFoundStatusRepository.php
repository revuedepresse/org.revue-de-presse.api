<?php

namespace App\Infrastructure\Publication\Repository;

use App\Domain\Publication\Entity\NotFoundStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Infrastructure\Api\Entity\ArchivedStatus;
use App\Infrastructure\Api\Entity\Status;
use App\Domain\Publication\StatusInterface;

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

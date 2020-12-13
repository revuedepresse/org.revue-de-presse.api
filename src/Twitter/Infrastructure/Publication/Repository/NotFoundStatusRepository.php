<?php

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\Twitter\Domain\Curation\Entity\NotFoundStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Domain\Publication\StatusInterface;

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

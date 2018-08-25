<?php

namespace App\Accessor;

use App\Status\Repository\NotFoundStatusRepository;
use Doctrine\ORM\EntityManager;
use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Repository\ArchivedStatusRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

class StatusAccessor
{
    /**
     * @var bool
     */
    public $accessingInternalApi = true;

    /**
     * @var ArchivedStatusRepository
     */
    public $archivedStatusRepository;

    /**
     * @var EntityManager
     */
    public $entityManager;

    /**
     * @var NotFoundStatusRepository
     */
    public $notFoundStatusRepository;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @param string $identifier
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function declareStatusNotFoundByIdentifier(string $identifier)
    {
        $status = $this->statusRepository->findOneBy(['statusId' => $identifier]);
        if (is_null($status)) {
            $status = $this->archivedStatusRepository
                ->findOneBy(['statusId' => $identifier]);
        }

        $existingRecord = false;
        if ($status instanceof Status) {
            $existingRecord = !is_null($this->notFoundStatusRepository->findOneBy(['status' => $status]));
        }

        if ($status instanceof ArchivedStatus) {
            $existingRecord = !is_null($this->notFoundStatusRepository->findOneBy(['archivedStatus' => $status]));
        }

        if ($existingRecord) {
            return;
        }

        $notFoundStatus = $this->notFoundStatusRepository->markStatusAsNotFound($status);

        $this->entityManager->persist($notFoundStatus);
        $this->entityManager->flush();
    }
}

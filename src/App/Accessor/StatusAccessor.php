<?php

namespace App\Accessor;

use App\Status\Entity\NullStatus;
use App\Status\Repository\NotFoundStatusRepository;
use Doctrine\ORM\EntityManager;
use WeavingTheWeb\Bundle\ApiBundle\Entity\ArchivedStatus;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Repository\ArchivedStatusRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WTW\UserBundle\Repository\UserRepository;

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
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var NotFoundStatusRepository
     */
    public $notFoundStatusRepository;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var UserRepository
     */
    public $userManager;

    /**
     * @var Accessor
     */
    public $accessor;

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

    /**
     * @param int $identifier
     * @return \API|NullStatus|array|mixed|object|\stdClass
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function refreshStatusByIdentifier(string $identifier)
    {
        $status = $this->statusRepository->findStatusIdentifiedBy($identifier);

        if (!is_null($status) && !empty($status)) {
            return $status;
        }

        $status = $this->accessor->showStatus($identifier);

        $this->entityManager->clear();

        try {
            $this->statusRepository->saveStatuses(
                [$status],
                $this->accessor->userToken,
                null,
                $this->logger
            );
        } catch (NotFoundMemberException $notFoundMemberException) {
            throw $notFoundMemberException;
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
        }

        $status = $this->statusRepository->findStatusIdentifiedBy($identifier);

        if (is_null($status)) {
            return new NullStatus();
        }

        return $status;
    }

    /**
     * @param string $screenName
     */
    public function ensureMemberHavingScreenNameExists(string $screenName)
    {
        $member = $this->accessor->showUser($screenName);
        $this->userManager->make($member->id, $member->screen_name);
    }
}

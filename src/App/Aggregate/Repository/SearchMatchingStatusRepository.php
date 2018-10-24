<?php

namespace App\Aggregate\Repository;

use App\Aggregate\Entity\SavedSearch;
use App\Aggregate\Entity\SearchMatchingStatus;
use Doctrine\ORM\EntityRepository;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

class SearchMatchingStatusRepository extends EntityRepository
{
    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @param SavedSearch $savedSearch
     * @param array       $statuses
     * @param string      $identifier
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveSearchMatchingStatus(
        SavedSearch $savedSearch,
        array $statuses,
        string $identifier
    ) {
        $result = $this->statusRepository->iterateOverStatuses(
            $statuses,
            $identifier,
            null,
            $this->logger
        );
        array_walk($result['statuses'], function (StatusInterface $status) use ($savedSearch) {
            $searchMatchingStatus = $this->findOneBy(['status' => $status, 'savedSearch' => $savedSearch]);
            if ($searchMatchingStatus instanceof SearchMatchingStatus) {
                return;
            }

            $searchMatchingStatus = new SearchMatchingStatus($status, $savedSearch);

            $this->getEntityManager()->persist($searchMatchingStatus);
        });

        $this->getEntityManager()->flush();
    }
}

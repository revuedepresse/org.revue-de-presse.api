<?php

namespace App\Aggregate\Repository;

use App\Aggregate\Entity\TimelyStatus;
use Doctrine\ORM\EntityRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

class TimelyStatusRepository extends EntityRepository
{
    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

    /**
     * @param array $properties
     * @return TimelyStatus
     */
    public function fromArray(array $properties)
    {
        $status = $this->statusRepository->findOneBy(['id' => $properties['status_id']]);
        $timelyStatus = $this->findOneBy([
            'status' => $status
        ]);

        if ($timelyStatus instanceof TimelyStatus) {
            return $timelyStatus->updateTimeRange();
        }

        $aggregate = $this->aggregateRepository->findOneBy([
            'id' => $properties['aggregate_id'],
            'screenName' => $properties['member_name']
        ]);

        return new TimelyStatus(
            $status,
            $aggregate,
            $status->getCreatedAt()
        );
    }

    /**
     * @param TimelyStatus $timelyStatus
     * @return TimelyStatus
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveTimelyStatus(TimelyStatus $timelyStatus)
    {
        $this->getEntityManager()->persist($timelyStatus);
        $this->getEntityManager()->flush();

        return $timelyStatus;
    }
}

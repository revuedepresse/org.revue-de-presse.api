<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use App\StatusCollection\Mapping\MappingAwareInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;
use WTW\UserBundle\Entity\User;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @method Status|null find($id, $lockMode = null, $lockVersion = null)
 * @method Status|null findOneBy(array $criteria, array $orderBy = null)
 * @method Status[]    findAll()
 * @method Status[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusRepository extends ArchivedStatusRepository
{
    /**
     * @var ArchivedStatusRepository
     */
    public $archivedStatusRepository;

    /**
     * @param $properties
     * @return Status
     */
    public function fromArray($properties)
    {
        $status = new Status();

        $status->setScreenName($properties['screen_name']);
        $status->setName($properties['name']);
        $status->setText($properties['text']);
        $status->setUserAvatar($properties['user_avatar']);
        $status->setIdentifier($properties['identifier']);
        $status->setCreatedAt($properties['created_at']);
        $status->setIndexed(false);

        if (array_key_exists('aggregate', $properties)) {
            $status->addToAggregates($properties['aggregate']);
        }

        return $status;
    }

    /**
     * @param MappingAwareInterface $service
     * @param ArrayCollection       $statuses
     * @return ArrayCollection
     */
    public function mapStatusCollectionToService(
        MappingAwareInterface $service,
        ArrayCollection $statuses
    ) {
        return $statuses->map(function (Status $status) use ($service) {
            return $service->apply($status);
        });
    }

    /**
     * @param Status $status
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Status $status)
    {
        $this->getEntityManager()->persist($status);
        $this->getEntityManager()->flush();
    }

    /**
     * @param ArrayCollection $statuses
     */
    public function saveBatch(ArrayCollection $statuses)
    {
        $statuses->map(function ($status) {
            $this->getEntityManager()->persist($status);
        });

        $this->getEntityManager()->flush();
    }

    public function setOauthTokens($oauthTokens)
    {
        $this->oauthTokens = $oauthTokens;

        return $this;
    }

    public function getAlias()
    {
        return 'status';
    }

    /**
     * @param      $hash
     * @return bool
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function existsAlready($hash)
    {
        if ($this->archivedStatusRepository->existsAlready($hash)) {
            return true;
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
            ->andWhere('s.hash = :hash');

        $queryBuilder->setParameter('hash', $hash);
        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        if ($this->logger) {
            $this->logger->info(
                sprintf(
                    '%d statuses already serialized for "%s"',
                    $count,
                    $hash
                )
            );
        }

        return $count > 0;
    }

    /**
     * @param $screenName
     * @return int|mixed
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException
     */
    public function countHowManyStatusesFor($screenName)
    {
        $member = $this->memberManager->findOneBy(['twitter_username' => $screenName]);
        if ($member instanceof User && $member->totalStatuses !== 0) {
            return $member->totalStatuses;
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();
        $totalStatuses = intval($totalStatuses) + $this->archivedStatusRepository->countHowManyStatusesFor($screenName);

        $this->memberManager->declareTotalStatusesOfMemberWithScreenName($totalStatuses, $screenName);

        return $totalStatuses;
    }

    /**
     * @param array $extract
     * @return \WeavingTheWeb\Bundle\ApiBundle\Entity\Status
     */
    public function updateResponseBody(array $extract): StatusInterface
    {
        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Status $userStream */
        $userStream = $this->findOneBy(['statusId' => $extract['status_id']]);

        if (!$userStream instanceof Status) {
            $userStream = $this->archivedStatusRepository->findOneBy(['statusId' => $extract['status_id']]);
        }

        $userStream->setApiDocument($extract['api_document']);
        $userStream->setIdentifier($extract['identifier']);

        return $userStream->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @param string    $memberScreenName
     * @param \DateTime $earliestDate
     * @param \DateTime $latestDate
     * @return ArrayCollection
     */
    public function selectStatusCollection(
        string $memberScreenName,
        \DateTime $earliestDate,
        \DateTime $latestDate
    ) {
        $queryBuilder = $this->createQueryBuilder('s');

        $this->between($queryBuilder, $earliestDate, $latestDate);

        $queryBuilder->andWhere('s.screenName = :screen_name');
        $queryBuilder->setParameter('screen_name', $memberScreenName);

        return new ArrayCollection($queryBuilder->getQuery()->getResult());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param \DateTime    $earliestDate
     * @param \DateTime    $latestDate
     */
    private function between(
        QueryBuilder $queryBuilder,
        \DateTime $earliestDate,
        \DateTime $latestDate
    ): void {
        $queryBuilder->andWhere('s.createdAt >= :after');
        $queryBuilder->setParameter('after', $earliestDate);

        $queryBuilder->andWhere('s.createdAt <= :before');
        $queryBuilder->setParameter('before', $latestDate);
    }

    /**
     * @param string    $aggregateName
     * @param \DateTime $earliestDate
     * @param \DateTime $latestDate
     * @return ArrayCollection
     */
    public function selectAggregateStatusCollection(
        string $aggregateName,
        \DateTime $earliestDate,
        \DateTime $latestDate
    ) {
        $queryBuilder = $this->createQueryBuilder('s');

        $this->between($queryBuilder, $earliestDate, $latestDate);

        $queryBuilder->join(
            's.aggregates',
            'a'
        );
        $queryBuilder->andWhere('a.name = :aggregate_name');
        $queryBuilder->setParameter('aggregate_name', $aggregateName);

        return new ArrayCollection($queryBuilder->getQuery()->getResult());
    }

    /**
     * @param        $screenName
     * @param string $direction
     * @param null   $before
     * @return array|mixed|StatusInterface
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException
     */
    protected function findNextExtremum($screenName, $direction = 'asc', $before = null): array
    {
        $nextExtremum = $this->archivedStatusRepository->findNextExtremum($screenName, $direction, $before);

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
            ->andWhere('s.screenName = :screenName')
            ->andWhere('s.apiDocument is not null')
            ->orderBy('s.statusId + 0', $direction)
            ->setMaxResults(1);

        $queryBuilder->setParameter('screenName', $screenName);

        if ($before) {
            $queryBuilder->andWhere('DATE(s.createdAt) = :date');
            $queryBuilder->setParameter('date', (new \DateTime($before))->format('Y-m-d'));
        }

        try {
            $extremum = $queryBuilder->getQuery()->getSingleResult();

            if ($direction === 'asc') {
                $nextMinimum = min(intval($extremum['statusId']), $nextExtremum['statusId']);

                return ['statusId' => $this->memberManager->declareMinStatusIdForMemberWithScreenName(
                    "$nextMinimum",
                    $screenName
                )->minStatusId];
            }

            $nextMaximum = max(intval($extremum['statusId']), $nextExtremum['statusId']);

            return ['statusId' => $this->memberManager->declareMaxStatusIdForMemberWithScreenName(
                "$nextMaximum",
                $screenName
            )->maxStatusId];
        } catch (NoResultException $exception) {
            return [];
        }
    }
}

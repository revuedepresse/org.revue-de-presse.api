<?php

namespace App\Api\Repository;

use App\Accessor\Exception\NotFoundStatusException;
use App\Membership\Entity\MemberInterface;
use App\StatusCollection\Mapping\MappingAwareInterface;
use App\Twitter\Exception\NotFoundMemberException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use App\Api\Entity\Aggregate;
use App\Api\Entity\Status;
use App\Api\Entity\StatusInterface;
use Doctrine\Persistence\ManagerRegistry;
use WeavingTheWeb\Bundle\TwitterBundle\Serializer\UserStatus;
use App\Membership\Entity\Member;

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
     * @throws NonUniqueResultException
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
        $count = (int) $queryBuilder->getQuery()->getSingleScalarResult();

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
     *
     * @return int|mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countHowManyStatusesFor($screenName)
    {
        $member = $this->memberManager->findOneBy(['twitter_username' => $screenName]);
        if ($member instanceof MemberInterface && $member->totalStatuses !== 0) {
            $status = $this->findOneBy(['screenName' => $screenName], ['createdAt' => 'DESC']);
            $decodedStatusDocument = json_decode($status->getApiDocument(), true);

            if ($decodedStatusDocument['user']['statuses_count'] > UserStatus::MAX_AVAILABLE_TWEETS_PER_USER) {
                return $decodedStatusDocument['user']['statuses_count'];
            }

            return $member->totalStatuses;
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();
        $totalStatuses = (int) $totalStatuses + $this->archivedStatusRepository->countHowManyStatusesFor($screenName);

        $this->memberManager->declareTotalStatusesOfMemberWithName($totalStatuses, $screenName);

        return $totalStatuses;
    }

    /**
     * @param $screenName
     * @return \App\Membership\Entity\MemberInterface
     * @throws NotFoundStatusException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateLastStatusPublicationDate($screenName)
    {
        /** @var User $member */
        $member = $this->memberManager->findOneBy(['twitter_username' => $screenName]);

        $lastStatus = $this->getLastKnownStatusFor($screenName);
        $member->lastStatusPublicationDate = $lastStatus->getCreatedAt();

        return $this->memberManager->saveMember($member);
    }

    /**
     * @param array $extract
     * @return \App\Api\Entity\Status
     */
    public function updateResponseBody(array $extract): StatusInterface
    {
        /** @var \App\Api\Entity\Status $userStream */
        $userStream = $this->findOneBy(['statusId' => $extract['status_id']]);

        if (!$userStream instanceof Status) {
            $userStream = $this->archivedStatusRepository->findOneBy(['statusId' => $extract['status_id']]);
        }

        $userStream->setApiDocument($extract['api_document']);
        $userStream->setIdentifier($extract['identifier']);
        $userStream->setText($extract['text']);

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
     * @param string         $screenName
     * @param string         $direction
     * @param \DateTime|null $before
     * @return array
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws NotFoundMemberException
     */
    public function findNextExtremum(string $screenName, string $direction = 'asc', \DateTime $before = null): array
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
            $queryBuilder->setParameter(
                'date',
                (new \DateTime($before, new \DateTimeZone('UTC')))
                    ->format('Y-m-d')
            );
        }

        try {
            $extremum = $queryBuilder->getQuery()->getSingleResult();

            if ($direction === 'asc') {
                $nextMinimum = min((int) $extremum['statusId'], $nextExtremum['statusId']);

                return ['statusId' => $this->memberManager->declareMinStatusIdForMemberWithScreenName(
                    "$nextMinimum",
                    $screenName
                )->minStatusId];
            }

            $nextMaximum = max((int) $extremum['statusId'], $nextExtremum['statusId']);

            return ['statusId' => $this->memberManager->declareMaxStatusIdForMemberWithScreenName(
                "$nextMaximum",
                $screenName
            )->maxStatusId];
        } catch (NoResultException $exception) {
            return [];
        }
    }

    /**
     * @param $status
     * @return \App\Membership\Entity\MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws NotFoundMemberException
     */
    public function declareMaximumStatusId($status)
    {
        $maxStatus = $status->id_str;

        return $this->memberManager->declareMaxStatusIdForMemberWithScreenName(
            $maxStatus,
            $status->user->screen_name
        );
    }

    /**
     * @param $status
     * @return \App\Membership\Entity\MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws NotFoundMemberException
     */
    public function declareMinimumStatusId($status)
    {
        $minStatus = $status->id_str;

        return $this->memberManager->declareMinStatusIdForMemberWithScreenName(
            $minStatus,
            $status->user->screen_name
        );
    }

    /**
     * @param        $status
     * @param string $memberName
     * @return \App\Membership\Entity\MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws NotFoundMemberException
     */
    public function declareMaximumLikedStatusId($status, string $memberName)
    {
        $maxStatus = $status->id_str;

        return $this->memberManager->declareMaxLikeIdForMemberWithScreenName(
            $maxStatus,
            $memberName
        );
    }

    /**
     * @param        $status
     * @param string $memberName
     * @return \App\Membership\Entity\MemberInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws NotFoundMemberException
     */
    public function declareMinimumLikedStatusId($status, string $memberName)
    {
        $minStatus = $status->id_str;

        return $this->memberManager->declareMinLikeIdForMemberWithScreenName(
            $minStatus,
            $memberName
        );
    }

    /**
     * @param Aggregate $aggregate
     * @return array
     */
    public function findByAggregate(Aggregate $aggregate)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.aggregates', 'a');
        $queryBuilder->andWhere('a.id = :id');
        $queryBuilder->setParameter('id', $aggregate->getId());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $screenName
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function howManyStatusesForMemberHavingScreenName($screenName): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $query = <<<QUERY
            SELECT count(*) total_statuses
            FROM weaving_status s
            WHERE s.ust_full_name = ?
QUERY;

        $statement = $connection->executeQuery($query, [$screenName], [\PDO::PARAM_STR]);

        return $statement->fetchAll();
    }

    /**
     * @param string $screenName
     * @return null|Status
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getLastKnownStatusForMemberHavingScreenName(string $screenName)
    {
        $connection = $this->getEntityManager()->getConnection();
        $query = <<<QUERY
              SELECT id, publication_date_time
              FROM (
                SELECT s.ust_id AS id,
                s.ust_created_at publication_date_time
                FROM weaving_status s
                WHERE s.ust_full_name = ?
              ) select_
              ORDER BY select_.publication_date_time DESC
QUERY;

        $statement = $connection->executeQuery($query, [$screenName], [\PDO::PARAM_STR]);
        $result = $statement->fetchAll();

        $criteria = ['id' => $result[0]['id']];
        $lastStatus = $this->findOneBy($criteria);
        return $lastStatus;
    }

    /**
     * @param string $screenName
     * @return null|Status
     * @throws NotFoundStatusException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getLastKnownStatusFor(string $screenName)
    {
        $result = $this->howManyStatusesForMemberHavingScreenName($screenName);

        $lastStatus = null;
        if ($result[0]['total_statuses'] > 0) {
            $lastStatus = $this->getLastKnownStatusForMemberHavingScreenName($screenName);
        }

        if (!$lastStatus instanceof StatusInterface) {
            throw new NotFoundStatusException(sprintf(
                'No status has been collected for member with screen name "%s"',
                $screenName
            ));
        }

        return $lastStatus;
    }
}

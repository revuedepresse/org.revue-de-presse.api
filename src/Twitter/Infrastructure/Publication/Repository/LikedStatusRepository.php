<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Domain\Curation\Entity\LikedStatus;
use App\Twitter\Domain\Publication\Repository\LikedStatusRepositoryInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Membership\Domain\Entity\MemberInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use const INF;

/**
 * @method LikedStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method LikedStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method LikedStatus[]    findAll()
 * @method LikedStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LikedStatusRepository extends ServiceEntityRepository implements ExtremumAwareInterface, LikedStatusRepositoryInterface
{
    /**
     * @var MemberRepositoryInterface
     */
    public MemberRepositoryInterface $memberRepository;

    /**
     * @param string $memberName
     * @param string $maxId
     * @param string $findingDirection
     *
     * @return array|int
     * @throws NonUniqueResultException
     */
    public function countCollectedLikes(
        string $memberName,
        $maxId,
        string $findingDirection = ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER
    )
    {
        $statusCount = $this->countLikedStatuses($memberName, $maxId);

        $archiveStatusQueryBuilder = $this->createQueryBuilder('l');
        $archiveStatusQueryBuilder->select('COUNT(DISTINCT archivedStatus.hash) as count_')
                                  ->join('l.archivedStatus', 'archivedStatus')
                                  ->andWhere('l.likedByMemberName = :memberName');
        $archiveStatusQueryBuilder->setParameter('memberName', $memberName);

        if ($findingDirection === ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER &&
            $maxId < INF) {
            $archiveStatusQueryBuilder->andWhere('(archivedStatus.statusId + 0) <= :maxId');
            $archiveStatusQueryBuilder->setParameter('maxId', $maxId);
        }

        if ($findingDirection === ExtremumAwareInterface::FINDING_IN_DESCENDING_ORDER) {
            $archiveStatusQueryBuilder->andWhere('(archivedStatus.statusId + 0) >= :maxId');
            $archiveStatusQueryBuilder->setParameter('maxId', $maxId);
        }

        try {
            $archivedStatusSingleResult = $archiveStatusQueryBuilder->getQuery()->getSingleResult();

            return $archivedStatusSingleResult['count_'] + $statusCount;
        } catch (NoResultException $exception) {
            return ['count_' => 0 + $statusCount];
        }
    }

    /**
     * @param string $memberName
     *
     * @return int|mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countHowManyLikesFor(string $memberName)
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $memberName]);
        if ($member instanceof MemberInterface && $member->totalLikes !== 0) {
            return $member->totalLikes;
        }

        $totalLikes = $this->countCollectedLikes(
            $memberName,
            INF
        );
        $this->memberRepository->declareTotalLikesOfMemberWithName($totalLikes, $memberName);

        return $totalLikes;
    }

    /**
     * @param MemberInterface $member
     * @param StatusInterface $status
     * @param MemberInterface $likedBy
     * @param Aggregate       $aggregate
     *
     * @return LikedStatus
     */
    public function ensureMemberStatusHasBeenMarkedAsLikedBy(
        MemberInterface $member,
        StatusInterface $status,
        MemberInterface $likedBy,
        Aggregate $aggregate
    ): LikedStatus {
        $likedStatus = $this->findOneBy(
            [
                'status'    => $status,
                'likedBy'   => $likedBy,
                'member'    => $member,
                'aggregate' => $aggregate
            ]
        );
        if ($likedStatus instanceof LikedStatus) {
            return $likedStatus;
        }

        $likedStatus = $this->findOneBy(
            [
                'status'  => $status,
                'likedBy' => $likedBy,
                'member'  => $member
            ]
        );
        if ($likedStatus instanceof LikedStatus) {
            return $likedStatus->setAggregate($aggregate);
        }

        return $this->fromMemberStatus(
            $status,
            $likedBy,
            $member,
            $aggregate
        );
    }

    public function findLocalMaximum(
        string $memberName,
        ?string $before = null
    ): array {
        return $this->findNextExtremum($memberName, 'asc', $before);
    }

    /**
     * @param string $memberName
     * @param string $direction
     * @param string $before
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextExtremum(
        string $memberName,
        string $direction = 'asc',
        ?string $before = null
    ): array {
        $nextExtremum = $this->findNextExtremumAmongArchivedStatuses(
            $memberName,
            $direction,
            $before
        );

        $member = $this->memberRepository->findOneBy(['twitter_username' => $memberName]);
        if ($member instanceof MemberInterface) {
            if ($direction === 'desc' && $member->maxLikeId !== null) {
                return ['statusId' => $member->maxLikeId];
            }

            if ($direction === 'asc' && $member->minLikeId !== null) {
                return ['statusId' => $member->minLikeId];
            }
        }

        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder->select('s.statusId')
                     ->join('l.status', 's')
                     ->andWhere('l.likedByMemberName = :memberName')
                     ->orderBy('s.statusId + 0', $direction)
                     ->setMaxResults(1);

        $queryBuilder->setParameter('memberName', $memberName);

        if ($before) {
            $queryBuilder->andWhere('DATE(l.publicationDateTime) = :date');
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

                return [
                    'statusId' => $this->memberRepository->declareMaxLikeIdForMemberWithScreenName(
                        "$nextMinimum",
                        $memberName
                    )->minStatusId
                ];
            }

            $nextMaximum = max((int) $extremum['statusId'], $nextExtremum['statusId']);

            return [
                'statusId' => $this->memberRepository->declareMaxLikeIdForMemberWithScreenName(
                    "$nextMaximum",
                    $memberName
                )->maxStatusId
            ];
        } catch (NoResultException $exception) {
            if ($nextExtremum['statusId'] === -INF || $nextExtremum['statusId'] === +INF) {
                return ['statusId' => null];
            }

            return ['statusId' => $nextExtremum['statusId']];
        }
    }

    /**
     * @param string $memberName
     *
     * @return array
     */
    public function getIdsOfExtremeStatusesSavedForMemberHavingScreenName(string $memberName): array
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $memberName]);

        return [
            'min_id' => $member->minLikeId,
            'max_id' => $member->maxLikeId
        ];
    }

    /**
     * @param \stdClass $status
     * @param string    $aggregateName
     * @param string    $likedByMemberName
     * @param string    $memberName
     *
     * @return bool
     * @throws \Exception
     */
    public function hasBeenSavedBefore(
        \stdClass $status,
        string $aggregateName,
        string $likedByMemberName,
        string $memberName
    ): bool {
        $query = <<<QUERY
            SELECT (count(*) > 0) status_has_been_saved_before
            FROM liked_status
            INNER JOIN weaving_status status
            WHERE ust_status_id = :status_id
            AND liked_status.status_id = status.ust_id
            AND liked_by_member_name = :liked_by_member_name
            AND aggregate_name = :aggregate_name
            AND member_name = :member_name
QUERY;

        $connection = $this->getEntityManager()->getConnection();
        $statement  = $connection->executeQuery(
            strtr(
                $query,
                [
                    ':status_id'            => (int) $status->id_str,
                    ':aggregate_name'       => $connection->quote($aggregateName),
                    ':liked_by_member_name' => $connection->quote($likedByMemberName),
                    ':member_name'          => $connection->quote($memberName),
                ]
            )
        );
        $results    = $statement->fetchAll()[0];

        return (bool) $results['status_has_been_saved_before'];
    }

    /**
     * @param LikedStatus $likedStatus
     *
     * @return LikedStatus
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(LikedStatus $likedStatus)
    {
        $this->getEntityManager()->persist($likedStatus);
        $this->getEntityManager()->flush();

        return $likedStatus;
    }

    /**
     * @param string $memberName
     * @param string $maxId
     *
     * @param string $findingDirection
     *
     * @return int
     * @throws NonUniqueResultException
     */
    private function countLikedStatuses(
        string $memberName,
        $maxId,
        string $findingDirection = ExtremumAwareInterface::FINDING_IN_DESCENDING_ORDER
    ): int
    {
        return $this->countLikes(
            $memberName,
            $maxId,
            'status',
            $findingDirection
        ) +
            $this->countLikes(
                $memberName,
                $maxId,
                'archivedStatus',
                $findingDirection
            );
    }

    /**
     * @param string $memberName
     * @param        $maxId
     *
     * @param string $joinColumn
     * @param string $findingDirection
     *
     * @return int
     * @throws NonUniqueResultException
     */
    private function countLikes(
        string $memberName,
        $maxId,
        string $joinColumn,
        string $findingDirection = ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER
    ): int {
        $statusQueryBuilder = $this->createQueryBuilder('l');

        $statusQueryBuilder->select('COUNT(DISTINCT status.hash) as count_')
                           ->join('l.' . $joinColumn, 'status')
                           ->andWhere('l.likedByMemberName = :memberName');
        $statusQueryBuilder->setParameter('memberName', $memberName);

        if ($findingDirection === ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER &&
            $maxId < INF) {
            $statusQueryBuilder->andWhere('(status.statusId + 0) <= :maxId');
            $statusQueryBuilder->setParameter('maxId', $maxId);
        }

        if ($findingDirection === ExtremumAwareInterface::FINDING_IN_DESCENDING_ORDER) {
            $statusQueryBuilder->andWhere('(status.statusId + 0) >= :maxId');
            $statusQueryBuilder->setParameter('maxId', $maxId);
        }

        try {
            $singleResult = $statusQueryBuilder->getQuery()->getSingleResult();

            return $singleResult['count_'];
        } catch (NoResultException $exception) {
            return 0;
        }
    }

    /**
     * @param string         $memberName
     * @param string         $direction
     * @param \DateTime|null $before
     *
     * @return array
     * @throws NonUniqueResultException
     */
    private function findNextExtremumAmongArchivedStatuses(
        string $memberName,
        string $direction = 'asc',
        \DateTime $before = null
    ): array {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $memberName]);
        if ($member instanceof MemberInterface) {
            if ($direction = 'desc' && !is_null($member->maxLikeId)) {
                return ['statusId' => $member->maxLikeId];
            }

            if ($direction = 'asc' && !is_null($member->minLikeId)) {
                return ['statusId' => $member->minLikeId];
            }
        }

        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder->select('s.statusId')
                     ->join('l.archivedStatus', 's')
                     ->andWhere('l.likedByMemberName = :memberName')
                     ->orderBy('s.statusId + 0', $direction)
                     ->setMaxResults(1);

        $queryBuilder->setParameter('memberName', $memberName);

        if ($before) {
            $queryBuilder->andWhere('DATE(l.publicationDateTime) = :date');
            $queryBuilder->setParameter(
                'date',
                (new \DateTime($before, new \DateTimeZone('UTC')))
                    ->format('Y-m-d')
            );
        }

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            if ($direction == 'asc') {
                return ['statusId' => +INF];
            }

            return ['statusId' => -INF];
        }
    }

    /**
     * @param StatusInterface $memberStatus
     * @param MemberInterface $likedBy
     * @param MemberInterface $member
     * @param Aggregate       $aggregate
     *
     * @return LikedStatus
     */
    private function fromMemberStatus(
        StatusInterface $memberStatus,
        MemberInterface $likedBy,
        MemberInterface $member,
        Aggregate $aggregate
    ) {
        return new LikedStatus($memberStatus, $likedBy, $aggregate, $member);
    }

}

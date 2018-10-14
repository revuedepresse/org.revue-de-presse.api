<?php

namespace App\Status\Repository;

use App\Member\MemberInterface;
use App\Status\Entity\LikedStatus;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;
use WTW\UserBundle\Repository\UserRepository;

/**
 * @method LikedStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method LikedStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method LikedStatus[]    findAll()
 * @method LikedStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LikedStatusRepository extends EntityRepository implements ExtremumAwareInterface
{
    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @param string $memberName
     * @return int|mixed
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countHowManyLikesFor(string $memberName)
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $memberName]);
        if ($member instanceof MemberInterface && $member->totalLikes !== 0) {
            return $member->totalLikes;
        }

        $totalLikes = $this->countCollectedLikes($memberName, INF);
        $this->memberRepository->declareTotalLikesOfMemberWithName($totalLikes, $memberName);

        return $totalLikes;
    }

    /**
     * @param string $memberName
     * @param int    $maxId
     * @return array|int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countCollectedLikes(string $memberName, $maxId)
    {
        $statusCount = $this->countLikedStatuses($memberName, $maxId);

        $archiveStatusQueryBuilder = $this->createQueryBuilder('l');
        $archiveStatusQueryBuilder->select('COUNT(DISTINCT archivedStatus.hash) as count_')
            ->join('l.archivedStatus', 'archivedStatus')
            ->andWhere('l.memberName = :memberName');
        $archiveStatusQueryBuilder->setParameter('memberName', $memberName);

        if ($maxId < INF) {
            $archiveStatusQueryBuilder->andWhere('(archivedStatus.statusId + 0) <= :maxId');
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
     * @param $memberName
     * @param $maxId
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function countLikedStatuses(string $memberName, $maxId): int
    {
        return $this->countLikes($memberName, $maxId, 'status') +
            $this->countLikes($memberName, $maxId, 'archivedStatus');
    }

    /**
     * @param $memberName
     * @param $maxId
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function countLikes(string $memberName, $maxId, string $joinColumn): int
    {
        $statusQueryBuilder = $this->createQueryBuilder('l');

        $statusQueryBuilder->select('COUNT(DISTINCT status.hash) as count_')
            ->join('l.'.$joinColumn, 'status')
            ->andWhere('l.memberName = :memberName');
        $statusQueryBuilder->setParameter('memberName', $memberName);

        if ($maxId < INF) {
            $statusQueryBuilder->andWhere('(status.statusId + 0) <= :maxId');
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
     * @param \DateTime|null $before
     * @return array
     */
    public function findLocalMaximum(string $memberName, \DateTime $before = null): array
    {
        return $this->findNextExtremum($memberName, 'asc', $before);
    }

    /**
     * @param string $memberName
     * @return array
     */
    public function findNextMinimum(string $memberName): array
    {
        return $this->findNextExtremum($memberName, 'desc');
    }

    /**
     * @param string $memberName
     * @return array
     */
    public function findNextMaximum(string $memberName): array
    {
        return $this->findNextExtremum($memberName, 'asc');
    }

    /**
     * @param string         $memberName
     * @param string         $direction
     * @param \DateTime|null $before
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findNextExtremum(
        string $memberName,
        string $direction = 'asc',
        \DateTime $before = null
    ): array {
        $nextExtremum = $this->findNextExtremumAmongArchivedStatuses(
            $memberName,
            $direction,
            $before
        );

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
            ->join('l.status', 's')
            ->andWhere('l.memberName = :memberName')
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
                $nextMinimum = min(intval($extremum['statusId']), $nextExtremum['statusId']);

                return ['statusId' => $this->memberRepository->declareMaxLikeIdForMemberWithScreenName(
                    "$nextMinimum",
                    $memberName
                )->minStatusId];
            }

            $nextMaximum = max(intval($extremum['statusId']), $nextExtremum['statusId']);

            return ['statusId' => $this->memberRepository->declareMaxLikeIdForMemberWithScreenName(
                "$nextMaximum",
                $memberName
            )->maxStatusId];
        } catch (NoResultException $exception) {
            if ($nextExtremum['statusId'] === -INF || $nextExtremum['statusId'] === +INF) {
                return ['statusId' => null];
            }

            return ['statusId' => $nextExtremum['statusId']];
        }
    }

    /**
     * @param string         $memberName
     * @param string         $direction
     * @param \DateTime|null $before
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
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
            ->andWhere('l.memberName = :memberName')
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
     * @param string $memberName
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
     * @param MemberInterface $member
     * @param StatusInterface $memberStatus
     * @param MemberInterface $likedBy
     * @return LikedStatus
     */
    public function ensureMemberStatusHasBeenMarkedAsLikedBy(
        MemberInterface $member,
        StatusInterface $status,
        MemberInterface $likedBy
    ): LikedStatus {
        $likedStatus = $this->findOneBy([
            'status' => $status,
            'likedBy' => $likedBy,
            'member' => $member
        ]);
        if ($likedStatus instanceof LikedStatus) {
            return $likedStatus;
        }

        return $this->fromMemberStatus(
            $status,
            $likedBy,
            $member
        );
    }

    /**
     * @param StatusInterface $memberStatus
     * @param MemberInterface $likedBy
     * @param MemberInterface $member
     * @return LikedStatus
     */
    private function fromMemberStatus(
        StatusInterface $memberStatus,
        MemberInterface $likedBy,
        MemberInterface $member
    ) {
        return new LikedStatus($memberStatus, $likedBy, $member);
    }

    /**
     * @param LikedStatus $likedStatus
     * @return LikedStatus
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(LikedStatus $likedStatus)
    {
        $this->getEntityManager()->persist($likedStatus);
        $this->getEntityManager()->flush();

        return $likedStatus;
    }
}

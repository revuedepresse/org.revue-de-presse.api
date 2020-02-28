<?php
declare(strict_types=1);

namespace App\Api\Repository;

use App\Api\Entity\Aggregate;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Exception\InsertDuplicatesException;
use App\Domain\Repository\StatusRepositoryInterface;
use App\Domain\Status\StatusInterface;
use App\Domain\Status\TaggedStatus;
use App\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Infrastructure\DependencyInjection\PublicationPersistenceTrait;
use App\Infrastructure\DependencyInjection\PublicationRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\StatusLoggerTrait;
use App\Infrastructure\DependencyInjection\Status\StatusPersistenceTrait;
use App\Infrastructure\DependencyInjection\TaggedStatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Infrastructure\Twitter\Api\Normalizer\Normalizer;
use App\Membership\Entity\MemberInterface;
use App\Operation\Collection\CollectionInterface;
use App\Status\Entity\LikedStatus;
use App\Status\Repository\ExtremumAwareInterface;
use App\Status\Repository\LikedStatusRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function count;
use const JSON_THROW_ON_ERROR;

/**
 * @package App\Api\Repository
 */
class ArchivedStatusRepository extends ResourceRepository implements
    ExtremumAwareInterface,
    StatusRepositoryInterface
{
    use MemberRepositoryTrait;
    use PublicationPersistenceTrait;
    use PublicationRepositoryTrait;
    use StatusLoggerTrait;
    use StatusPersistenceTrait;
    use TaggedStatusRepositoryTrait;
    use TimelyStatusRepositoryTrait;

    public ManagerRegistry $registry;

    public LoggerInterface $appLogger;

    public LikedStatusRepository $likedStatusRepository;

    public Connection $connection;

    public bool $shouldExtractProperties;

    /**
     * @param $screenName
     * @param $maxId
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function countCollectedStatuses($screenName, $maxId)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.hash) as count_')
                     ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        if ($maxId < INF) {
            $queryBuilder->andWhere('(s.statusId + 0) <= :maxId');
            $queryBuilder->setParameter('maxId', $maxId);
        }

        try {
            $singleResult = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            $singleResult = ['count_' => null];
        }

        return $singleResult['count_'];
    }

    /**
     * @param $screenName
     *
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countHowManyStatusesFor($screenName): int
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
                     ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();

        return (int) $totalStatuses;
    }

    /**
     * @param string        $screenName
     * @param string|null $before
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findLocalMaximum(
        string $screenName,
        ?string $before = null
    ): array {
        $direction = self::FINDING_IN_ASCENDING_ORDER;
        if ($before === null) {
            $direction = self::FINDING_IN_DESCENDING_ORDER;
        }

        return $this->findNextExtremum($screenName, $direction, $before);
    }

    /**
     * @param string        $screenName
     * @param string        $direction
     * @param string $before
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextExtremum(
        string $screenName,
        string $direction = self::FINDING_IN_ASCENDING_ORDER,
        ?string $before = null
    ): array {
        $member = $this->memberRepository->findOneBy([
            'twitter_username' => $screenName
        ]);
        if ($member instanceof MemberInterface) {
            if ($direction === self::FINDING_IN_DESCENDING_ORDER &&
                $member->maxStatusId !== null) {
                return ['statusId' => $member->maxStatusId];
            }

            if ($direction === self::FINDING_IN_ASCENDING_ORDER &&
                $member->minStatusId !== null) {
                return ['statusId' => $member->minStatusId];
            }
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
                     ->andWhere('s.screenName = :screenName')
                     ->andWhere('s.apiDocument is not null')
                     ->orderBy('s.statusId + 0', $direction)
                     ->setMaxResults(1);

        $queryBuilder->setParameter('screenName', $screenName);

        if ($before) {
            $queryBuilder->andWhere('DATE(s.createdAt) <= :date');
            $queryBuilder->setParameter(
                'date',
                (new DateTime($before, new \DateTimeZone('UTC')))
                    ->format('Y-m-d')
            );
        }

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            $this->appLogger->info($exception->getMessage());

            if ($direction === self::FINDING_IN_ASCENDING_ORDER) {
                return ['statusId' => +INF];
            }

            return ['statusId' => -INF];
        }
    }

    /**
     * @param string $id
     *
     * @return array|StatusInterface|TaggedStatus
     * @throws Exception
     */
    public function findStatusIdentifiedBy(string $id)
    {
        $status = $this->findOneBy(['statusId' => $id]);
        if (!($status instanceof StatusInterface)) {
            return [];
        }

        if (!$this->shouldExtractProperties) {
            return $status;
        }

        $statusDocument = json_decode(
            $status->getApiDocument(),
            false,
            512,
            JSON_THROW_ON_ERROR
        );

        return Normalizer::normalizeAll(
            [$statusDocument],
            function ($properties) {
                return $properties;
            },
            $this->appLogger
        )->first()->toLegacyProps();
    }

    public function getAlias()
    {
        return 'archived_status';
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
            'min_id' => $member->minStatusId,
            'max_id' => $member->maxStatusId
        ];
    }

    /**
     * @param array $statuses
     *
     * @return bool
     * @throws Exception
     */
    public function hasBeenSavedBefore(array $statuses): bool
    {
        if (count($statuses) === 0) {
            throw new \Exception('There should be one item at least');
        }

        $identifier         = '';
        $statusesProperties = Normalizer::normalizeAll(
            [$statuses[0]],
            function ($extract) use ($identifier) {
                $extract['identifier'] = $identifier;

                return $extract;
            },
            $this->appLogger
        );

        return $this->taggedStatusRepository->archivedStatusHavingHashExists(
            $statusesProperties->first()->hash()
        );
    }

    public function reviseDocument(TaggedStatus $taggedStatus): StatusInterface
    {
        /** @var Status $status */
        $status = $this->findOneBy(
            ['statusId' => $taggedStatus->documentId()]
        );

        $status->setApiDocument($taggedStatus->document());
        $status->setIdentifier($taggedStatus->token());
        $status->setText($taggedStatus->text());

        return $status->setUpdatedAt(
            new DateTime(
                'now',
                new \DateTimeZone('UTC')
            )
        );
    }

    /**
     * @param array           $statuses
     * @param                 $identifier
     * @param Aggregate       $aggregate
     * @param LoggerInterface $logger
     * @param MemberInterface $likedBy
     * @param callable        $ensureMemberExists
     *
     * @return CollectionInterface
     */
    public function saveLikes(
        array $statuses,
        $identifier,
        ?Aggregate $aggregate,
        LoggerInterface $logger,
        MemberInterface $likedBy,
        callable $ensureMemberExists
    ): CollectionInterface {
        $this->appLogger = $logger;

        $statusIds = $this->getStatusIdentities($statuses);

        $indexedStatus = $this->createIndexOfExistingStatus($statusIds);

        $extracts = Normalizer::normalizeAll(
            $statuses,
            function ($extract) use ($identifier, $indexedStatus) {
                $extract['identifier'] = $identifier;

                return $extract;
            },
            $this->appLogger
        );

        $likedStatuses = [];

        $entityManager = $this->getEntityManager();

        /** @var TaggedStatus $taggedStatus */
        foreach ($extracts->toArray() as $key => $taggedStatus) {
            $extract      = $taggedStatus->toLegacyProps();

            $memberStatus = null;
            if (array_key_exists($taggedStatus->documentId(), $indexedStatus)) {
                $memberStatus = $indexedStatus[$taggedStatus->documentId()];
            }

            if (!($memberStatus instanceof StatusInterface)) {
                $memberStatus = $this->taggedStatusRepository
                    ->convertPropsToStatus($extract, $aggregate);
            }

            if ($memberStatus->getId() === null) {
                $this->collectStatusLogger->logStatus($memberStatus);
            }

            if ($memberStatus instanceof ArchivedStatus) {
                $memberStatus = $this->statusPersistence->unarchiveStatus(
                    $memberStatus,
                    $entityManager
                );
            }

            try {
                if ($memberStatus->getId()) {
                    $memberStatus->setUpdatedAt(
                        new DateTime(
                            'now',
                            new \DateTimeZone('UTC')
                        )
                    );
                }

                try {
                    $member = $ensureMemberExists($memberStatus->getScreenName());
                } catch (ProtectedAccountException|SuspendedAccountException|NotFoundMemberException $exception) {
                    $member =
                        $this->memberRepository->findOneBy(['twitter_username' => $memberStatus->getScreenName()]);
                }

                if ($member instanceof MemberInterface) {
                    $likedStatuses[] = $this->likedStatusRepository->ensureMemberStatusHasBeenMarkedAsLikedBy(
                        $member,
                        $memberStatus,
                        $likedBy,
                        $aggregate
                    );

                    $entityManager->persist($memberStatus);
                }
            } catch (ORMException $exception) {
                if ($exception->getMessage() === ORMException::entityManagerClosed()->getMessage()) {
                    $entityManager = $this->registry->resetManager('default');
                    $entityManager->persist($memberStatus);
                }
            } catch (Exception $exception) {
                $this->appLogger->critical($exception->getMessage());
                continue;
            }
        }

        $this->flushAndResetManagerOnUniqueConstraintViolation($entityManager);
        $this->flushLikedStatuses($likedStatuses, $entityManager);

        if (count($extracts) > 0) {
            $this->memberRepository->incrementTotalLikesOfMemberWithName(
                count($extracts),
                $likedBy->getTwitterUsername()
            );
        }

        return $extracts;
    }

    /**
     * @param array $statusIds
     *
     * @return array
     */
    private function createIndexOfExistingStatus(array $statusIds): array
    {
        $indexedStatuses = [];

        if (count($statusIds) > 0) {
            $queryBuilder = $this->createQueryBuilder('s');
            $queryBuilder->andWhere('s.statusId in (:ids)');
            $queryBuilder->setParameter('ids', $statusIds);
            $existingStatuses = $queryBuilder->getQuery()->getResult();

            array_walk(
                $existingStatuses,
                function (StatusInterface $existingStatus) use (&$indexedStatuses) {
                    $indexedStatuses[$existingStatus->getStatusId()] = $existingStatus;
                }
            );
        }

        return $indexedStatuses;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    private function flushAndResetManagerOnUniqueConstraintViolation(
        EntityManagerInterface $entityManager
    ): void {
        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            $this->registry->resetManager('default');

            InsertDuplicatesException::throws($exception);
        }
    }

    /**
     * @param                        $likedStatuses
     * @param EntityManagerInterface $entityManager
     */
    private function flushLikedStatuses(
        $likedStatuses,
        EntityManagerInterface $entityManager
    ): void {
        (new ArrayCollection($likedStatuses))->map(
            function (LikedStatus $likedStatus) use ($entityManager) {
                $entityManager->persist($likedStatus);
            }
        );

        $this->flushAndResetManagerOnUniqueConstraintViolation(
            $entityManager
        );
    }

    /**
     * @param array $statuses
     *
     * @return array
     */
    private function getStatusIdentities(array $statuses): array
    {
        return array_map(
            function ($status) {
                return $status->id_str;
            },
            $statuses
        );
    }
}

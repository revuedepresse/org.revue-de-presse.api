<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Persistence;

use App\Twitter\Infrastructure\Api\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Infrastructure\Api\Exception\InsertDuplicatesException;
use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Twitter\Domain\Publication\StatusCollection;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Domain\Publication\TaggedStatus;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusLoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TaggedStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Twitter\Domain\Publication\Repository\TimelyStatusRepositoryInterface;
use App\Twitter\Infrastructure\Twitter\Api\Normalizer\Normalizer;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;
use Closure;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use function count;
use function is_array;

class StatusPersistence implements StatusPersistenceInterface
{
    use ApiAccessorTrait;
    use LoggerTrait;
    use PublicationPersistenceTrait;
    use PublishersListRepositoryTrait;
    use StatusLoggerTrait;
    use StatusRepositoryTrait;
    use TaggedStatusRepositoryTrait;
    use TimelyStatusRepositoryTrait;

    public const PROPERTY_NORMALIZED_STATUS = 'normalized_status';
    public const PROPERTY_SCREEN_NAME       = 'screen_name';
    public const PROPERTY_STATUS            = 'status';

    public ManagerRegistry $registry;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $appLogger;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(
        TimelyStatusRepositoryInterface $timelyStatusRepository,
        ManagerRegistry $registry,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->registry               = $registry;
        $this->timelyStatusRepository = $timelyStatusRepository;
        $this->entityManager          = $entityManager;
        $this->appLogger              = $logger;
    }

    public function persistAllStatuses(
        array $statuses,
        AccessToken $accessToken,
        Aggregate $aggregate = null
    ): array {
        $propertiesCollection = Normalizer::normalizeAll(
            $statuses,
            $this->tokenSetter($accessToken),
            $this->appLogger
        );

        $statusCollection = StatusCollection::fromArray([]);

        /** @var TaggedStatus $taggedStatus */
        foreach ($propertiesCollection->toArray() as $key => $taggedStatus) {
            try {
                $statusCollection = $this->persistStatus(
                    $statusCollection,
                    $taggedStatus,
                    $aggregate
                );
            } catch (ORMException $exception) {
                if ($exception->getMessage() === ORMException::entityManagerClosed()->getMessage()) {
                    $this->entityManager = $this->registry->resetManager('default');
                }
            } catch (Exception $exception) {
                $this->appLogger->info($exception->getMessage());
            }
        }

        $this->flushAndResetManagerOnUniqueConstraintViolation($this->entityManager);

        $firstStatus = $statusCollection->first();
        $screenName  = $firstStatus instanceof StatusInterface ?
            $firstStatus->getScreenName() :
            null;

        return [
            self::PROPERTY_NORMALIZED_STATUS => $propertiesCollection,
            self::PROPERTY_SCREEN_NAME       => $screenName,
            self::PROPERTY_STATUS            => $statusCollection
        ];
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

    private function logStatusToBeInserted(StatusInterface $status): void
    {
        if ($status->getId() === null) {
            $this->collectStatusLogger->logStatus($status);
        }
    }

    private function persistStatus(
        CollectionInterface $statuses,
        TaggedStatus $taggedStatus,
        ?Aggregate $aggregate
    ): CollectionInterface {
        $extract = $taggedStatus->toLegacyProps();
        $status  = $this->taggedStatusRepository
            ->convertPropsToStatus($extract, $aggregate);

        $this->logStatusToBeInserted($status);

        $status = $this->unarchiveStatus($status, $this->entityManager);
        $this->refreshUpdatedAt($status);

        $this->persistTimelyStatus($aggregate, $status);

        $this->entityManager->persist($status);

        return $statuses->add($status);
    }

    private function persistTimelyStatus(
        ?Aggregate $aggregate,
        StatusInterface $status
    ): void {
        if ($aggregate instanceof Aggregate) {
            $timelyStatus = $this->timelyStatusRepository->fromAggregatedStatus(
                $status,
                $aggregate
            );
            $this->entityManager->persist($timelyStatus);
        }
    }

    private function refreshUpdatedAt(StatusInterface $status): void
    {
        if ($status->getId()) {
            try {
                $status->setUpdatedAt(
                    new DateTime('now', new DateTimeZone('UTC'))
                );
            } catch (Exception $exception) {
                $this->appLogger->error($exception->getMessage());
            }
        }
    }

    private function tokenSetter(AccessToken $accessToken): Closure
    {
        return function ($extract) use ($accessToken) {
            $extract['identifier'] = $accessToken->accessToken();

            return $extract;
        };
    }

    /**
     * @param StatusInterface        $status
     * @param EntityManagerInterface $entityManager
     *
     * @return Status
     */
    public function unarchiveStatus(
        StatusInterface $status,
        EntityManagerInterface $entityManager
    ): StatusInterface {
        if (!($status instanceof ArchivedStatus)) {
            return $status;
        }

        $archivedStatus = $status;
        $status         = Status::fromArchivedStatus($archivedStatus);

        $entityManager->remove($archivedStatus);

        return $status;
    }

    public function savePublicationsForScreenName(
        array $statuses,
        string $screenName,
        CollectionStrategyInterface $collectionStrategy
    ) {
        $success = null;

        if (!is_array($statuses) || count($statuses) === 0) {
            return $success;
        }

        $publishersList = null;
        $publishersListId = $collectionStrategy->publishersListId();
        if ($publishersListId !== null) {
            /** @var Aggregate $publishersList */
            $publishersList = $this->publishersListRepository->findOneBy(
                ['id' => $publishersListId]
            );
        }

        $this->collectStatusLogger->logHowManyItemsHaveBeenFetched(
            $statuses,
            $screenName
        );

        $likedBy = null;
        if ($collectionStrategy->fetchLikes()) {
            $likedBy = $this->apiAccessor->ensureMemberHavingNameExists($screenName);
        }

        $savedStatuses = $this->saveStatuses(
            $statuses,
            $collectionStrategy,
            $publishersList,
            $likedBy
        );

        return $this->collectStatusLogger->logHowManyItemsHaveBeenSaved(
            $savedStatuses->count(),
            $screenName,
            $collectionStrategy->fetchLikes()
        );
    }

    /**
     * @param array                       $statuses
     * @param CollectionStrategyInterface $collectionStrategy
     * @param Aggregate|null              $publishersList
     * @param MemberInterface|null        $likedBy
     *
     * @return CollectionInterface
     */
    private function saveStatuses(
        array $statuses,
        CollectionStrategyInterface $collectionStrategy,
        Aggregate $publishersList = null,
        MemberInterface $likedBy = null
    ): CollectionInterface {
        if ($likedBy !== null && $collectionStrategy->fetchLikes()) {
            return $this->statusRepository->saveLikes(
                $statuses,
                $this->apiAccessor->getOAuthToken(),
                $publishersList,
                $this->logger,
                $likedBy,
                function ($memberName) {
                    return $this->apiAccessor->ensureMemberHavingNameExists($memberName);
                }
            );
        }

        return $this->publicationPersistence->persistStatusPublications(
            $statuses,
            new AccessToken($this->apiAccessor->getOAuthToken()),
            $publishersList
        );
    }
}
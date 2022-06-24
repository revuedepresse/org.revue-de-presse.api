<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Persistence;

use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Http\Entity\Status;
use App\Twitter\Infrastructure\Http\Exception\InsertDuplicatesException;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Infrastructure\Publication\Dto\StatusCollection;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\Publication\Dto\TaggedStatus;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusLoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TaggedStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Twitter\Domain\Publication\Repository\TimelyStatusRepositoryInterface;
use App\Twitter\Infrastructure\Http\Normalizer\Normalizer;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use Closure;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use function count;
use function is_array;

class StatusPersistence implements StatusPersistenceInterface
{
    use HttpClientTrait;
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
        PublishersList $twitterList = null
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
                    $twitterList
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
        ?PublishersList $twitterList
    ): CollectionInterface {
        $extract = $taggedStatus->toLegacyProps();
        $status  = $this->taggedStatusRepository
            ->convertPropsToStatus($extract, $twitterList);

        $this->logStatusToBeInserted($status);

        $status = $this->unarchiveStatus($status, $this->entityManager);
        $this->refreshUpdatedAt($status);

        $this->persistTimelyStatus($twitterList, $status);

        $this->entityManager->persist($status);

        return $statuses->add($status);
    }

    private function persistTimelyStatus(
        ?PublishersList $twitterList,
        StatusInterface $status
    ): void {
        if ($twitterList instanceof PublishersList) {
            $timelyStatus = $this->timelyStatusRepository->fromAggregatedStatus(
                $status,
                $twitterList
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
        CurationSelectorsInterface $selectors
    ) {
        $success = null;

        if (!is_array($statuses) || count($statuses) === 0) {
            return $success;
        }

        $twitterList = null;
        $publishersListId = $selectors->publishersListId();
        if ($publishersListId !== null) {
            /** @var PublishersList $twitterList */
            $twitterList = $this->publishersListRepository->findOneBy(
                ['id' => $publishersListId]
            );
        }

        $this->collectStatusLogger->logHowManyItemsHaveBeenFetched(
            $statuses,
            $screenName
        );

        $savedStatuses = $this->saveStatuses(
            $statuses,
            $selectors,
            $twitterList
        );

        return $this->collectStatusLogger->logHowManyItemsHaveBeenSaved(
            $savedStatuses->count(),
            $screenName
        );
    }

    private function saveStatuses(
        array                      $statuses,
        CurationSelectorsInterface $selectors,
        PublishersList $twitterList = null
    ): CollectionInterface {
        return $this->publicationPersistence->persistStatusPublications(
            $statuses,
            new AccessToken($this->apiClient->getAccessToken()),
            $twitterList
        );
    }
}
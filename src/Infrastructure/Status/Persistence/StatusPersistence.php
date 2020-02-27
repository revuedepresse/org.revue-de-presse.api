<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Persistence;

use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Exception\InsertDuplicatesException;
use App\Domain\Status\StatusCollection;
use App\Domain\Status\StatusInterface;
use App\Domain\Status\TaggedStatus;
use App\Infrastructure\DependencyInjection\StatusLoggerTrait;
use App\Infrastructure\DependencyInjection\TaggedStatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Infrastructure\Repository\Status\TimelyStatusRepositoryInterface;
use App\Infrastructure\Twitter\Api\Normalizer\Normalizer;
use App\Operation\Collection\CollectionInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class StatusPersistence implements StatusPersistenceInterface
{
    use StatusLoggerTrait;
    use TaggedStatusRepositoryTrait;
    use TimelyStatusRepositoryTrait;

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
        $screenName = $firstStatus instanceof StatusInterface ? $firstStatus->getScreenName() : null;

        return [
            'extracts'    => $propertiesCollection,
            'screen_name' => $screenName,
            'statuses'    => $statusCollection
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
                    new \DateTime('now', new \DateTimeZone('UTC'))
                );
            } catch (\Exception $exception) {
                $this->appLogger->error($exception->getMessage());
            }
        }
    }

    private function tokenSetter(AccessToken $accessToken): \Closure
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
    private function unarchiveStatus(
        StatusInterface $status,
        EntityManagerInterface $entityManager
    ): StatusInterface {
        if (!($status instanceof ArchivedStatus)) {
            return $status;
        }

        $archivedStatus = $status;
        $status = Status::fromArchivedStatus($archivedStatus);

        $entityManager->remove($archivedStatus);

        return $status;
    }
}
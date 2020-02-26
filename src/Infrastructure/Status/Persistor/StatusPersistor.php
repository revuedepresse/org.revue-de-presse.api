<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Persistor;

use App\Aggregate\Repository\TimelyStatusRepository;
use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Exception\InsertDuplicatesException;
use App\Domain\Status\StatusInterface;
use App\Domain\Status\TaggedStatus;
use App\Infrastructure\DependencyInjection\StatusLoggerTrait;
use App\Infrastructure\DependencyInjection\TaggedStatusRepositoryTrait;
use App\Infrastructure\Twitter\Api\Normalizer\Normalizer;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class StatusPersistor implements StatusPersistorInterface
{
    use StatusLoggerTrait;
    use TaggedStatusRepositoryTrait;

    public ManagerRegistry $registry;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $appLogger;

    /**
     * @var TimelyStatusRepository
     */
    private TimelyStatusRepository $timelyStatusRepository;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(
        TimelyStatusRepository $timelyStatusRepository,
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
            function ($extract) use ($accessToken) {
                $extract['identifier'] = $accessToken->accessToken();

                return $extract;
            },
            $this->appLogger
        );

        $statuses = [];

        /** @var TaggedStatus $taggedStatus */
        foreach ($propertiesCollection->toArray() as $key => $taggedStatus) {
            $extract = $taggedStatus->toLegacyProps();
            $status  = $this->taggedStatusRepository
                ->convertPropsToStatus($extract, $aggregate);

            if ($status->getId() === null) {
                $this->collectStatusLogger->logStatus($status);
            }

            if ($status instanceof ArchivedStatus) {
                $status = $this->unarchiveStatus($status, $this->entityManager);
            }

            try {
                if ($status->getId()) {
                    $status->setUpdatedAt(
                        new DateTime(
                            'now',
                            new \DateTimeZone('UTC')
                        )
                    );
                }

                if ($aggregate instanceof Aggregate) {
                    $timelyStatus = $this->timelyStatusRepository->fromAggregatedStatus(
                        $status,
                        $aggregate
                    );
                    $this->entityManager->persist($timelyStatus);
                }

                $this->entityManager->persist($status);

                $statuses[] = $status;
            } catch (ORMException $exception) {
                if ($exception->getMessage() === ORMException::entityManagerClosed()->getMessage()) {
                    $this->entityManager = $this->registry->resetManager('default');
                    $this->entityManager->persist($status);
                }
            } catch (Exception $exception) {
                $this->appLogger->info($exception->getMessage());
            }
        }

        $this->flushAndResetManagerOnUniqueConstraintViolation($this->entityManager);

        return [
            'extracts'    => $propertiesCollection,
            'screen_name' => $extract['screen_name'] ?? null,
            'statuses'    => $statuses
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

    /**
     * @param ArchivedStatus         $archivedStatus
     * @param EntityManagerInterface $entityManager
     *
     * @return Status
     */
    private function unarchiveStatus(
        ArchivedStatus $archivedStatus,
        EntityManagerInterface $entityManager
    ): StatusInterface {
        $status = Status::fromArchivedStatus($archivedStatus);

        $entityManager->remove($archivedStatus);

        return $status;
    }
}
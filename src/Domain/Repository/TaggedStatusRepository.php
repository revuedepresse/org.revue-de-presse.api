<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Api\Entity\Aggregate;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Api\Entity\StatusInterface;
use App\Domain\Status\TaggedStatus;
use App\Infrastructure\DependencyInjection\StatusRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Psr\Log\LoggerInterface;
use function sprintf;

class TaggedStatusRepository implements TaggedStatusRepositoryInterface
{
    use StatusRepositoryTrait;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager    = $entityManager;
        $this->statusRepository = $entityManager->getRepository(
            Status::class
        );
        $this->logger           = $logger;
    }

    /**
     * @param array          $properties
     * @param Aggregate|null $aggregate
     *
     * @return StatusInterface
     * @throws Exception
     */
    public function convertPropsToStatus(
        array $properties,
        Aggregate $aggregate = null
    ): StatusInterface {
        $taggedStatus = TaggedStatus::fromLegacyProps($properties);

        if ($this->statusHavingHashExists($taggedStatus->hash())) {
            $status = $this->statusRepository->reviseDocument($taggedStatus);

            if ($this->logger) {
                $this->logger->info(
                    sprintf(
                        'Updating response body of status with hash "%s" for member with screen_name "%s"',
                        $taggedStatus->hash(),
                        $taggedStatus->screenName()
                    )
                );
            }

            return $status;
        }

        return $taggedStatus->toStatus(
            $this->entityManager,
            $this->logger,
            $aggregate
        );
    }

    /**
     * @param $hash
     *
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function archivedStatusHavingHashExists($hash): bool
    {
        $queryBuilder = $this->entityManager
            ->getRepository(ArchivedStatus::class)
            ->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
                     ->andWhere('s.hash = :hash');

        $queryBuilder->setParameter('hash', $hash);
        $count = (int) $queryBuilder->getQuery()->getSingleScalarResult();

        $this->logger->info(
            sprintf(
                '%d statuses already serialized for "%s"',
                $count,
                $hash
            )
        );

        return $count > 0;
    }

    /**
     * @param $hash
     *
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function statusHavingHashExists($hash): bool
    {
        if ($this->archivedStatusHavingHashExists($hash)) {
            return true;
        }

        $queryBuilder = $this->entityManager
            ->getRepository(Status::class)
            ->createQueryBuilder('s');
        $queryBuilder->select('count(s.id) as count_')
                     ->andWhere('s.hash = :hash');

        $queryBuilder->setParameter('hash', $hash);
        $count = (int) $queryBuilder->getQuery()->getSingleScalarResult();

        $this->logger->info(
            sprintf(
                '%d statuses already serialized for "%s"',
                $count,
                $hash
            )
        );

        return $count > 0;
    }
}
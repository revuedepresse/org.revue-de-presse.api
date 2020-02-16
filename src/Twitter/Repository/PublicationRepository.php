<?php
declare(strict_types=1);

namespace App\Twitter\Repository;

use App\Api\Adapter\StatusToArray;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\StatusInterface;
use App\Operation\Collection\Collection;
use App\Operation\Collection\CollectionInterface;
use App\Twitter\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @package App\Twitter\Entity
 */
class PublicationRepository extends ServiceEntityRepository implements PublicationRepositoryInterface
{
    public EntityManagerInterface $entityManager;

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager  = $entityManager;
    }

    public function migrateStatusesToPublications(): void
    {
        $statuses = $this->findLastBatchOfStatus();

        while ($statuses->count() > 0) {
            $ids = $statuses->map(function (StatusInterface $status) {
                return $status->getId();
            });

            $this->persistPublications(
                new Collection(
                    StatusToArray::fromStatusCollection($statuses)->toArray()
                )
            );
            $this->markStatusAsPublished($ids);

            $statuses = $this->findLastBatchOfStatus();
        }
    }

    /**
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function persistPublications(
        CollectionInterface $collection
    ): CollectionInterface {
        $publications = Collection::fromArray([]);
        if ($collection instanceof CollectionInterface) {
            $publications = $collection->map(
                function ($item) {
                    $publication = Publication::fromArray($item);
                    $this->entityManager->persist($publication);

                    return $publication;
                }
            );
        }

        $this->entityManager->flush();

        return $publications;
    }

    /**
     * @return CollectionInterface
     */
    private function findLastBatchOfStatus(): CollectionInterface
    {
        $classMetadata = $this->entityManager->getClassMetadata(ArchivedStatus::class);
        $entityRepository = new EntityRepository($this->entityManager, $classMetadata);

        $status = $entityRepository->findBy(
                ['isPublished' => 0],
                null,
                100
            );

        return new Collection($status);
    }

    /**
     * @param CollectionInterface $ids
     *
     * @throws DBALException
     */
    private function markStatusAsPublished(CollectionInterface $ids): void
    {
        /** @var CollectionInterface $ids */
        if ($ids->count() > 0) {
            $this->entityManager->getConnection()->executeQuery(
                <<<QUERY
                    UPDATE weaving_archived_status 
                    SET is_published = 1
                    WHERE ust_id in (:ids)
QUERY
                ,
                [':ids' => $ids->toArray()],
                [':ids' => Connection::PARAM_INT_ARRAY]
            );
        }
    }
}
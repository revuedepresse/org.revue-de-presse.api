<?php
declare(strict_types=1);

namespace App\Twitter\Repository;

use App\Api\Adapter\StatusToArray;
use App\Api\Entity\StatusInterface;
use App\Api\Repository\ArchivedStatusRepository;
use App\Api\Repository\StatusRepository;
use App\Operation\Collection\Collection;
use App\Operation\Collection\CollectionInterface;
use App\Twitter\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use function array_map;

/**
 * @package App\Twitter\Entity
 */
class PublicationRepository extends ServiceEntityRepository implements PublicationRepositoryInterface
{
    public StatusRepository $statusRepository;

    public function setStatusRepository(StatusRepository $statusRepository): void
    {
        $this->statusRepository = $statusRepository;
    }

    public ArchivedStatusRepository $archivedStatusRepository;

    public function setArchivedStatusRepository(ArchivedStatusRepository $archivedStatusRepository): void
    {
        $this->archivedStatusRepository  = $archivedStatusRepository;
    }

    public function migrateStatusesToPublications(): void
    {
        $statuses = $this->statusRepository->findBy(
            ['isPublished' => false],
            null,
            100000
        );

        array_map(function (StatusInterface $status) {
            $status->markAsPublished();
            $this->getEntityManager()->persist($status);
        }, $statuses);

        $this->persistPublications(
            new Collection(StatusToArray::fromStatusCollection($statuses))
        );

        $this->getEntityManager()->flush();
    }

    /**
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persistPublications(
        CollectionInterface $collection
    ): CollectionInterface {
        $entityManager = $this->getEntityManager();

        $publications = Collection::fromArray([]);
        if ($collection instanceof CollectionInterface) {
            $publications = $collection->map(
                function ($item) use ($entityManager) {
                    $publication = Publication::fromArray($item);
                    $entityManager->persist($publication);

                    return $publication;
                }
            );
        }

        $entityManager->flush();

        return $publications;
    }
}
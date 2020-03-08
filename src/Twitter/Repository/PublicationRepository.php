<?php
declare(strict_types=1);

namespace App\Twitter\Repository;

use App\Api\Adapter\StatusToArray;
use App\Api\Entity\ArchivedStatus;
use App\Api\Entity\Status;
use App\Domain\Status\StatusInterface;
use App\Infrastructure\DependencyInjection\Formatter\PublicationFormatterTrait;
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
    use PublicationFormatterTrait;

    private const TABLE_ALIAS = 'p';

    public EntityManagerInterface $entityManager;

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager  = $entityManager;
    }

    /**
     * @throws DBALException
     */
    public function migrateStatusesToPublications(): void
    {
        $this->migrateStatusToPublications();

        $this->migrateArchivedStatusToPublications();
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
        $increment = 0;

        if ($collection instanceof CollectionInterface) {
            $publications = $collection->map(
                function ($item) use (&$increment) {
                    $publication = Publication::fromArray($item);

                    $existingPublication = $this->findOneBy([
                        'hash' => $publication->getHash()
                    ]);

                    if ($existingPublication === null) {
                        $this->entityManager->persist($publication);
                    }

                    if ($increment % 1000) {
                        $this->entityManager->flush();
                    }

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
    private function findLastBatchOfArchivedStatus(): CollectionInterface
    {
        $classMetadata = $this->entityManager->getClassMetadata(ArchivedStatus::class);
        $entityRepository = new EntityRepository($this->entityManager, $classMetadata);

        $status = $entityRepository->findBy(
                ['isPublished' => 0],
                null,
                1000
            );

        return new Collection($status);
    }

    /**
     * @return CollectionInterface
     */
    private function findLastBatchOfStatus(): CollectionInterface
    {
        $classMetadata = $this->entityManager->getClassMetadata(Status::class);
        $entityRepository = new EntityRepository($this->entityManager, $classMetadata);

        $queryBuilder = $entityRepository->createQueryBuilder('s')
            ->andWhere('s.isPublished = :is_published')
            ->setParameter('is_published', false)
            ->groupBy('s.statusId')
            ->setMaxResults(10000);

        $status = $queryBuilder->getQuery()->execute();

        return new Collection($status);
    }

    /**
     * @param CollectionInterface $ids
     *
     * @throws DBALException
     */
    private function markArchivedStatusAsPublished(CollectionInterface $ids): void
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
                    UPDATE weaving_status 
                    SET is_published = 1
                    WHERE ust_id in (:ids)
QUERY
                ,
                [':ids' => $ids->toArray()],
                [':ids' => Connection::PARAM_INT_ARRAY]
            );
        }
    }

    private function migrateArchivedStatusToPublications(): void
    {
        $archivedStatus = $this->findLastBatchOfArchivedStatus();

        while ($archivedStatus->count() > 0) {
            $ids = $archivedStatus->map(
                function (StatusInterface $status) {
                    return $status->getId();
                }
            );

            $this->persistPublications(
                new Collection(
                    StatusToArray::fromStatusCollection($archivedStatus)->toArray()
                )
            );
            $this->markArchivedStatusAsPublished($ids);

            $archivedStatus = $this->findLastBatchOfArchivedStatus();
        }
    }

    /**
     * @return mixed
     * @throws DBALException
     */
    private function migrateStatusToPublications()
    {
        $statuses = $this->findLastBatchOfStatus();

        while ($statuses->count() > 0) {
            $ids = $statuses->map(
                function (StatusInterface $status) {
                    return $status->getId();
                }
            );

            $this->persistPublications(
                new Collection(
                    StatusToArray::fromStatusCollection($statuses)
                        ->toArray()
                )
            );
            $this->markStatusAsPublished($ids);

            $statuses = $this->findLastBatchOfStatus();
        }
    }

    public function getLatestPublications(): Collection
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);

        $queryBuilder->select(self::TABLE_ALIAS);
        $queryBuilder->addSelect('
            JSON_EXTRACT('.self::TABLE_ALIAS.".document, '$.retweet_count') AS totalRetweets, 
            JSON_EXTRACT(".self::TABLE_ALIAS.".document, '$.favorite_count') AS favoriteCount
        ");
        $queryBuilder->andWhere('DATE('.self::TABLE_ALIAS.'.publishedAt) = DATE(NOW())');
        $queryBuilder->orderBy('totalRetweets',  'desc');
        $queryBuilder->setMaxResults(100);

        $result = $queryBuilder->getQuery()->getResult();
        $result = array_map(
            function ($row) {
                return $row[0];
            },
            $result
        );

        return $this->publicationFormatter->format(new Collection($result));
    }
}
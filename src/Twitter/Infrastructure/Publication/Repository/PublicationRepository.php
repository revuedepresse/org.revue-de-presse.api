<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\Twitter\Domain\Publication\Entity\Publication;
use App\Twitter\Domain\Publication\Repository\PublicationRepositoryInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\Api\Adapter\StatusToArray;
use App\Twitter\Infrastructure\Api\Entity\ArchivedStatus;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Infrastructure\DependencyInjection\Formatter\PublicationFormatterTrait;
use App\Twitter\Infrastructure\Operation\Collection\Collection;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * App\Twitter\Domain\Curation\Entity
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
            JSON_EXTRACT(".self::TABLE_ALIAS.".document, '$.favorite_count') AS favoriteCount,
            a.name as aggregateName
        ");
        $queryBuilder->innerJoin(
            Status::class,
            's',
            Join::WITH,
            self::TABLE_ALIAS.'.documentId = s.statusId'
        );
        $queryBuilder->join(
            's.aggregates',
            'a'
        );
        $queryBuilder->andWhere('DATE('.self::TABLE_ALIAS.'.publishedAt) = DATE(NOW())');
        $queryBuilder->having('totalRetweets < 100');
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
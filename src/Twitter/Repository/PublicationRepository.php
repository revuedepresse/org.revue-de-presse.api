<?php
declare(strict_types=1);

namespace App\Twitter\Repository;

use App\Operation\Collection\Collection;
use App\Operation\Collection\CollectionInterface;
use App\Twitter\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * @package App\Twitter\Entity
 */
class PublicationRepository extends ServiceEntityRepository implements PublicationRepositoryInterface
{
    /**
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function savePublications(CollectionInterface $collection): CollectionInterface {
        $entityManager = $this->getEntityManager();

        $publications = Collection::fromArray([]);
        if ($collection instanceof CollectionInterface) {
            $publications = $collection->map(function ($item) use ($entityManager) {
                $publication = Publication::fromArray($item);
                $entityManager->persist($publication);

                return $publication;
            });
        }

        $entityManager->flush();

        return $publications;
    }
}
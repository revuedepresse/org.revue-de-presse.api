<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Repository\PublishersListRouteRepositoryInterface;
use App\NewsReview\Domain\Exception\PublishersListRouteAlreadyExposedException;
use App\NewsReview\Domain\Routing\Model\RouteCollectionInterface;
use App\NewsReview\Infrastructure\Routing\Collection\PublishersListRouteCollection;
use App\NewsReview\Infrastructure\Routing\Entity\PublishersListRoute;
use App\NewsReview\Domain\Routing\Model\RouteInterface;
use App\NewsReview\Domain\Routing\Model\PublishersListInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ramsey\Uuid\Rfc4122\UuidV4;

/**
 * @method RouteInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteInterface[]    findAll()
 * @method RouteInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PublishersListRouteRepository extends ServiceEntityRepository implements PublishersListRouteRepositoryInterface
{
    public function exposePublishersList(
        PublishersListInterface $publishersList,
        string $hostname
    ): RouteInterface {
        $existingRoute = $this->findOneBy([
            'hostname' => $hostname,
            'publicId' => $publishersList->publicId()]
        );

        if ($existingRoute instanceof RouteInterface) {
            PublishersListRouteAlreadyExposedException::throws($publishersList);
        }

        $route = $this->openPublishersListRouteAt($publishersList, $hostname);

        $entityManager = $this->getEntityManager();

        $entityManager->persist($route);
        $entityManager->flush();

        return $route;
    }

    public function allPublishersRoutes(): RouteCollectionInterface
    {
        return PublishersListRouteCollection::fromArray($this->findAll());
    }

    private function openPublishersListRouteAt(
        PublishersListInterface $publishersList,
        string $hostname
    ): RouteInterface {
        return new PublishersListRoute(
            UuidV4::uuid4(),
            $publishersList->publicId(),
            $hostname
        );
    }
}
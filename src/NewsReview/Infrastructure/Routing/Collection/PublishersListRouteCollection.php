<?php
declare (strict_types=1);

namespace App\NewsReview\Infrastructure\Routing\Collection;

use App\NewsReview\Domain\Routing\Model\RouteCollectionInterface;
use App\NewsReview\Domain\Routing\Model\RouteInterface;
use App\NewsReview\Infrastructure\Routing\Entity\PublishersListRoute;

class PublishersListRouteCollection implements RouteCollectionInterface
{
    private array $routes;

    private function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public static function fromArray(array $publishersListRoutes): RouteCollectionInterface {
        $routes = array_filter(
            array_map(
                static function ($publishersListRoute) {
                    if ($publishersListRoute instanceof RouteInterface) {
                        return $publishersListRoute;
                    }
                },
                $publishersListRoutes
            )
        );

        return new self($routes);
    }

    public function toArray(): array
    {
        return array_map(
            static fn (RouteInterface $route): array => $route->toArray(),
            $this->routes
        );
    }
}
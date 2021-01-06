<?php

namespace App\NewsReview\Domain\Repository;

use App\NewsReview\Domain\Routing\Model\RouteCollectionInterface;
use App\NewsReview\Domain\Routing\Model\RouteInterface;
use App\NewsReview\Domain\Routing\Model\PublishersListInterface;

interface PublishersListRouteRepositoryInterface
{
    public function exposePublishersList(
        PublishersListInterface $publishersList,
        string $hostname
    ): RouteInterface;

    public function allPublishersRoutes(): RouteCollectionInterface;
}
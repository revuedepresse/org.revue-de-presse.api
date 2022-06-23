<?php

declare (strict_types=1);

namespace App\NewsReview\Domain\Routing\Model;

interface RouteCollectionInterface
{
    public static function fromArray(array $publishersListRoutes): RouteCollectionInterface;

    public function toArray(): array;
}
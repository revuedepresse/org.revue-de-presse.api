<?php

namespace App\Trends\Domain\Repository;

interface PopularPublicationRepositoryInterface
{
    public function findBy(SearchParamsInterface $searchParams): array;
}

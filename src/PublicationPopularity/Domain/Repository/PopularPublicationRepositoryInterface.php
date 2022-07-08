<?php

namespace App\PublicationPopularity\Domain\Repository;

interface PopularPublicationRepositoryInterface
{
    public function findBy(SearchParamsInterface $searchParams): array;
}

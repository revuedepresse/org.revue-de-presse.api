<?php

namespace App\NewsReview\Domain\Repository;

interface PopularPublicationRepositoryInterface
{
    public function findBy(SearchParamsInterface $searchParams): array;
}
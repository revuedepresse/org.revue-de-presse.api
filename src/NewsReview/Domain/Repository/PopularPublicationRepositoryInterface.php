<?php

namespace App\NewsReview\Domain\Repository;

interface PopularPublicationRepositoryInterface
{
    public function getFallbackPublishersListFingerprint(): string;

    public function findBy(SearchParamsInterface $searchParams): array;
}
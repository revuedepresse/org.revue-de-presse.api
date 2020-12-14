<?php
declare (strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\Repository;

use App\NewsReview\Domain\Repository\PopularPublicationRepositoryInterface;
use App\NewsReview\Domain\Repository\SearchParamsInterface;

class InMemoryPopularPublicationRepository implements PopularPublicationRepositoryInterface
{
    private array $highlights;

    public function __construct()
    {
        $this->highlights = json_decode(base64_decode(
            file_get_contents(__DIR__.'/../../../Resources/Response/ListHighlights.b64')
        ), true, 512, JSON_THROW_ON_ERROR);
    }

    public function findBy(SearchParamsInterface $searchParams): array
    {
        return $this->highlights;
    }
}
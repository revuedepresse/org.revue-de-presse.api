<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\NewsReview\Domain\Model\HighlightDto;
use App\NewsReview\Infrastructure\ApiPlatform\State\HighlightCollectionProvider;

#[ApiResource(
    shortName: 'Highlight',
    operations: [
        new GetCollection(
            uriTemplate: '/highlights',
            paginationEnabled: true,
            paginationItemsPerPage: 25,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            cacheHeaders: [
                'max_age'        => 3600,
                'shared_max_age' => 3600,
                'public'         => true,
                'vary'           => ['Accept', 'Authorization'],
            ],
            security: 'is_granted("ROLE_USER")',
            provider: HighlightCollectionProvider::class,
            output: HighlightDto::class,
        ),
    ],
)]
final class Highlight
{
}

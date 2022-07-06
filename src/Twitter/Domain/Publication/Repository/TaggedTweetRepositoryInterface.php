<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Publication\TweetInterface;

interface TaggedTweetRepositoryInterface
{
    public function convertPropsToStatus(
        array $properties,
        ?PublishersList $aggregate
    ): TweetInterface;

    public function archivedStatusHavingHashExists(string $hash): bool;

    public function statusHavingHashExists(string $hash): bool;
}
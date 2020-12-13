<?php
declare(strict_types=1);

namespace App\Domain\Publication\Repository;

use App\Infrastructure\Api\Entity\Aggregate;
use App\Domain\Publication\StatusInterface;

interface TaggedStatusRepositoryInterface
{
    public function convertPropsToStatus(
        array $properties,
        ?Aggregate $aggregate
    ): StatusInterface;

    public function archivedStatusHavingHashExists(string $hash): bool;

    public function statusHavingHashExists(string $hash): bool;
}
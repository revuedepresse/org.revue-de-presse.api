<?php
declare(strict_types=1);

namespace App\Domain\Publication\Repository;

use App\Infrastructure\Api\Entity\Aggregate;
use App\Domain\Publication\StatusInterface;

interface TimelyStatusRepositoryInterface
{
    public function fromAggregatedStatus(
        StatusInterface $status,
        ?Aggregate $aggregaste = null
    );
}
<?php
declare(strict_types=1);

namespace App\Domain\Status\Repository;

use App\Api\Entity\Aggregate;
use App\Domain\Status\StatusInterface;

interface TimelyStatusRepositoryInterface
{
    public function fromAggregatedStatus(
        StatusInterface $status,
        ?Aggregate $aggregaste = null
    );
}
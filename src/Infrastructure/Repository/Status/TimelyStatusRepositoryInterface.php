<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Status;

use App\Api\Entity\Aggregate;
use App\Domain\Status\StatusInterface;

interface TimelyStatusRepositoryInterface
{
    public function fromAggregatedStatus(
        StatusInterface $status,
        ?Aggregate $aggregaste = null
    );
}
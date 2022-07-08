<?php
declare(strict_types=1);

namespace App\Trends\Domain\Entity;

use App\Twitter\Domain\Publication\StatusInterface;

class StatusPopularity
{
    private string $id;

    private StatusInterface $status;

    private int $totalRetweets;

    private int $totalFavorites;

    private \DateTimeInterface $checkedAt;
}

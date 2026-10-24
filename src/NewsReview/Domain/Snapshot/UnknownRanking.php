<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Snapshot;

final class UnknownRanking extends \InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('Unknown ranking "%s".', $value));
    }
}

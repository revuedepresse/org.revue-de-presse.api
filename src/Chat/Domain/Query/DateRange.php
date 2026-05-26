<?php
declare(strict_types=1);

namespace App\Chat\Domain\Query;

final readonly class DateRange
{
    public function __construct(
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->from === null && $this->to === null;
    }
}

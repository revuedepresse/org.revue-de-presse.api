<?php
declare(strict_types=1);

namespace App\Chat\Domain\Query;

final readonly class QueryFilters
{
    /**
     * @param list<string> $screenNames
     */
    public function __construct(
        public DateRange $dateRange = new DateRange(),
        public array $screenNames = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->dateRange->isEmpty() && $this->screenNames === [];
    }
}

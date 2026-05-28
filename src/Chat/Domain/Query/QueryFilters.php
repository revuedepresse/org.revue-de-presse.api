<?php
declare(strict_types=1);

namespace App\Chat\Domain\Query;

final readonly class QueryFilters
{
    /**
     * @param list<string> $screenNames
     * @param bool $isSummary true when the user asked for a thematic synthesis
     *   (e.g. "Résume la semaine"). Downstream components branch on this to:
     *     - PromptBuilder: swap to a synthesis-style system prompt
     *     - DoctrinePublicationRetriever: bump K, diversify outlets, weight popularity
     *     - SymfonyAiChatStreamer: raise max_tokens
     *   Intentionally orthogonal to dateRange/screenNames so any combination works.
     */
    public function __construct(
        public DateRange $dateRange = new DateRange(),
        public array $screenNames = [],
        public bool $isSummary = false,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->dateRange->isEmpty() && $this->screenNames === [];
    }
}

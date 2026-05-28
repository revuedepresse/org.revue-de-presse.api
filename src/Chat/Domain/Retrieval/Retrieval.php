<?php
declare(strict_types=1);

namespace App\Chat\Domain\Retrieval;

/**
 * Result of one retrieval attempt: the matching hits, plus an optional
 * notice describing any compromise the retriever had to make to return
 * them (e.g. "we had to drop the date filter to find anything"). The
 * notice flows downstream so PromptBuilder can tell Mistral about it.
 */
final readonly class Retrieval
{
    /**
     * @param list<RetrievedHit> $hits
     */
    public function __construct(
        public array $hits,
        public ?RetrievalNotice $notice = null,
    ) {
    }
}

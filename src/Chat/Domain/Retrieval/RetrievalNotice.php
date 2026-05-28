<?php
declare(strict_types=1);

namespace App\Chat\Domain\Retrieval;

/**
 * Discrete shortcoming surfaced by the retriever. PromptBuilder maps each
 * case to a French sentence that goes above the extracts block, so the
 * assistant turn can acknowledge the gap instead of pretending the
 * returned extracts match the user's original ask.
 *
 * Add cases here as more fallback / degradation paths surface.
 */
enum RetrievalNotice: string
{
    /**
     * The user named a date window (e.g. "cette semaine") AND an outlet
     * (e.g. "telerama"), but no row matches both at once. The retriever
     * dropped the date filter and returned the outlet's archive instead.
     */
    case DATE_FILTER_RELAXED = 'date_filter_relaxed';
}

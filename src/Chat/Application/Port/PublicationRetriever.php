<?php
declare(strict_types=1);

namespace App\Chat\Application\Port;

use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Domain\Retrieval\Retrieval;

/**
 * Hybrid retrieval: embed the query, run pgvector cosine search, optionally
 * filter by metadata (date range, screen_name).
 *
 * Returns a Retrieval (hits + optional notice). The notice lets the
 * retriever flag compromises (e.g. "had to drop the date filter") so the
 * prompt downstream can have the assistant acknowledge them.
 */
interface PublicationRetriever
{
    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): Retrieval;
}

<?php
declare(strict_types=1);

namespace App\Chat\Application\Port;

use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Domain\Retrieval\RetrievedHit;

/**
 * Hybrid retrieval: embed the query, run pgvector cosine search, optionally
 * filter by metadata (date range, screen_name). The concrete adapter wraps
 * symfony/ai-store's Retriever + PostgresStore.
 */
interface PublicationRetriever
{
    /**
     * @return list<RetrievedHit> nearest-first, capped at $k
     */
    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): array;
}

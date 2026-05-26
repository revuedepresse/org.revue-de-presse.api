<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Application\Port\PublicationRetriever;
use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Domain\Retrieval\RetrievedHit;
use Symfony\AI\Store\Retriever;

/**
 * Adapter over symfony/ai-store's Retriever (vectorize query → PostgresStore::query()).
 *
 * v0.9 caveat: the exact shape of $options accepted by the Retriever / Store
 * `query()` is undocumented beyond `['limit' => N]`. The metadata-JSONB filter
 * branch below is the expected shape but MUST be verified against the
 * resolved package source at composer install time. If `$options['filter']`
 * is not supported in v0.9, fall back to over-retrieving (e.g. limit*4) and
 * filtering in PHP — see TODO comment.
 */
final class SymfonyAiPublicationRetriever implements PublicationRetriever
{
    public function __construct(private readonly Retriever $retriever)
    {
    }

    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): array
    {
        $options = ['limit' => $k];

        // TODO(v0.9-API-check): confirm Retriever supports filters in $options.
        if (!$filters->isEmpty()) {
            $filterPredicates = [];
            if ($filters->screenNames !== []) {
                $filterPredicates['screen_name'] = $filters->screenNames;
            }
            if ($filters->dateRange->from !== null) {
                $filterPredicates['snapshot_date_from'] = $filters->dateRange->from->format('Y-m-d');
            }
            if ($filters->dateRange->to !== null) {
                $filterPredicates['snapshot_date_to'] = $filters->dateRange->to->format('Y-m-d');
            }
            $options['filter'] = $filterPredicates;
        }

        $documents = $this->retriever->retrieve($cleanedQuery, $options);

        $hits = [];
        foreach ($documents as $document) {
            $metadata = method_exists($document, 'getMetadata') ? $document->getMetadata() : null;
            $rawMeta = $metadata !== null && method_exists($metadata, 'toArray')
                ? $metadata->toArray()
                : [];

            $id = method_exists($document, 'getId')
                ? (string) $document->getId()
                : (string) ($rawMeta['publication_id'] ?? '');
            $content = method_exists($document, 'getContent') ? (string) $document->getContent() : '';
            $distance = method_exists($document, 'getDistance') ? (float) $document->getDistance() : 0.0;

            $hits[] = new RetrievedHit(
                publicationId: $id,
                screenName: (string) ($rawMeta['screen_name'] ?? ''),
                snapshotDate: (string) ($rawMeta['snapshot_date'] ?? ''),
                url: (string) ($rawMeta['url'] ?? ''),
                text: $content,
                reposts: (int) ($rawMeta['reposts'] ?? 0),
                likes: (int) ($rawMeta['likes'] ?? 0),
                distance: $distance,
            );
        }

        return $hits;
    }
}

<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Application\Port\PublicationRetriever;
use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Domain\Retrieval\RetrievedHit;
use Symfony\AI\Store\RetrieverInterface;

/**
 * Adapter over symfony/ai-store's Retriever (vectorize query → PostgresStore::query()).
 *
 * The bundle's PostgresStore::query() accepts `$options['limit']`; richer
 * metadata-JSONB filters aren't part of the public schema in v0.9, so we
 * pull a wider K and apply screen_name / date-range filters in PHP after
 * the fact. For an outlet/date filter combined with K=8, we over-retrieve
 * by 4× and trim.
 */
final class SymfonyAiPublicationRetriever implements PublicationRetriever
{
    public function __construct(private readonly RetrieverInterface $retriever)
    {
    }

    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): array
    {
        $fetchLimit = $filters->isEmpty() ? $k : $k * 4;
        $documents = $this->retriever->retrieve($cleanedQuery, ['limit' => $fetchLimit]);

        $hits = [];
        foreach ($documents as $document) {
            $rawMeta = $document->getMetadata()->getArrayCopy();
            $screenName = isset($rawMeta['screen_name']) && \is_string($rawMeta['screen_name'])
                ? $rawMeta['screen_name']
                : '';
            $snapshotDate = isset($rawMeta['snapshot_date']) && \is_string($rawMeta['snapshot_date'])
                ? $rawMeta['snapshot_date']
                : '';

            if ($filters->screenNames !== [] && !\in_array($screenName, $filters->screenNames, true)) {
                continue;
            }
            if ($filters->dateRange->from !== null && $snapshotDate < $filters->dateRange->from->format('Y-m-d')) {
                continue;
            }
            if ($filters->dateRange->to !== null && $snapshotDate > $filters->dateRange->to->format('Y-m-d')) {
                continue;
            }

            // Cosine score: 1 = identical, 0 = orthogonal. We expose `distance`
            // = 1 - score so the cosine-distance threshold in RunChatTurn
            // (`<= 0.6`) keeps its intuitive meaning.
            $score = $document->getScore();
            $distance = $score !== null ? max(0.0, 1.0 - $score) : 1.0;

            // Row id is now a UUIDv5 hash of the at-proto URI (see
            // SymfonyAiPublicationEmbedder); surface the original URI from
            // metadata. Fall back to the raw id for legacy rows written
            // before the migration to UUIDv5 ids.
            $publicationId = isset($rawMeta['publication_id']) && \is_string($rawMeta['publication_id'])
                ? $rawMeta['publication_id']
                : (string) $document->getId();

            $hits[] = new RetrievedHit(
                publicationId: $publicationId,
                screenName: $screenName,
                snapshotDate: $snapshotDate,
                url: isset($rawMeta['url']) && \is_string($rawMeta['url']) ? $rawMeta['url'] : '',
                text: isset($rawMeta['_text']) && \is_string($rawMeta['_text'])
                    ? $rawMeta['_text']
                    : (isset($rawMeta['content']) && \is_string($rawMeta['content']) ? $rawMeta['content'] : ''),
                reposts: isset($rawMeta['reposts']) && \is_int($rawMeta['reposts']) ? $rawMeta['reposts'] : 0,
                likes: isset($rawMeta['likes']) && \is_int($rawMeta['likes']) ? $rawMeta['likes'] : 0,
                distance: $distance,
            );

            if (\count($hits) >= $k) {
                break;
            }
        }

        return $hits;
    }
}

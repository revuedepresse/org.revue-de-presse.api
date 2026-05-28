<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Doctrine;

use App\Chat\Application\Port\PublicationRetriever;
use App\Chat\Domain\Query\DateRange;
use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Domain\Retrieval\Retrieval;
use App\Chat\Domain\Retrieval\RetrievalNotice;
use App\Chat\Domain\Retrieval\RetrievedHit;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\AI\Store\Document\VectorizerInterface;

/**
 * Direct pgvector retrieval that pushes screen_name and date-range filters
 * into the SQL WHERE clause, so pgvector ranks the K closest rows AMONG the
 * filtered subset.
 *
 * Replaces SymfonyAiPublicationRetriever for the chat-publications path. The
 * symfony/ai-store v0.9 PostgresStore wraps the table but only exposes
 * limit, not arbitrary WHERE; that forced us to over-fetch K*4 candidates and
 * filter in PHP, which silently dropped to 0 hits when the top-4K didn't
 * happen to contain any row matching the filter (common when the filtered
 * outlet is a minority of the corpus). Going DBAL-direct keeps us on the
 * same `chat_publication_embedding` table (created by `ai:store:setup`) but
 * lets the database do the filtering at the right layer.
 */
final class DoctrinePublicationRetriever implements PublicationRetriever
{
    private const TABLE = 'chat_publication_embedding';

    public function __construct(
        private readonly Connection $connection,
        private readonly VectorizerInterface $vectorizer,
    ) {
    }

    /**
     * Recency-bias coefficient applied to the cosine-distance ORDER BY when
     * NO explicit date filter is set. The penalty is `λ · (age_days / 30)`,
     * so a 30-day-old doc costs +0.10 distance, a 90-day-old +0.30. With
     * cosine distances typically in [0.40, 0.60], that's enough to pull
     * recent-but-close hits above older-but-closest hits, without burying
     * older content that's a strong semantic match.
     *
     * When the user pinned a date window explicitly, the WHERE clause
     * already restricts the result set; adding decay inside that window
     * would skew within-window ordering without benefit.
     */
    private const RECENCY_BIAS_LAMBDA_PER_30_DAYS = 0.10;

    public function retrieve(string $cleanedQuery, int $k, QueryFilters $filters): Retrieval
    {
        $vector = $this->vectorizer->vectorize($cleanedQuery);
        $queryVec = '[' . implode(',', $vector->getData()) . ']';

        $hits = $this->runQuery($queryVec, $k, $filters);

        // Fallback: when a date filter combined with an outlet filter yields
        // zero hits, the corpus simply lacks recent content from that outlet.
        // Drop the date filter and retry so the user still gets real content
        // from the named outlet, instead of an empty card panel + a
        // confabulated assistant response (Mistral writes [N] markers from
        // the system prompt's strong cite-everything instruction regardless
        // of whether real extracts exist).
        //
        // We deliberately do NOT drop the screen_name filter on fallback —
        // switching to a different outlet would surprise the user. The
        // returned Retrieval carries DATE_FILTER_RELAXED so the prompt
        // builder can have Mistral acknowledge the gap.
        if ($hits === []
            && $filters->screenNames !== []
            && !$filters->dateRange->isEmpty()
        ) {
            $hits = $this->runQuery(
                $queryVec,
                $k,
                new QueryFilters(dateRange: new DateRange(), screenNames: $filters->screenNames),
            );
            if ($hits !== []) {
                return new Retrieval(hits: $hits, notice: RetrievalNotice::DATE_FILTER_RELAXED);
            }
        }

        return new Retrieval(hits: $hits);
    }

    /**
     * @return list<RetrievedHit>
     */
    private function runQuery(string $queryVec, int $k, QueryFilters $filters): array
    {
        $where = [];
        $params = ['query_vec' => $queryVec, 'limit' => $k];
        $types = ['query_vec' => ParameterType::STRING, 'limit' => ParameterType::INTEGER];
        $hasExplicitDateFilter = !$filters->dateRange->isEmpty();

        if ($filters->screenNames !== []) {
            $where[] = "metadata->>'screen_name' IN (:screen_names)";
            $params['screen_names'] = $filters->screenNames;
            $types['screen_names'] = ArrayParameterType::STRING;
        }

        if ($filters->dateRange->from !== null) {
            $where[] = "metadata->>'snapshot_date' >= :date_from";
            $params['date_from'] = $filters->dateRange->from->format('Y-m-d');
            $types['date_from'] = ParameterType::STRING;
        }
        if ($filters->dateRange->to !== null) {
            $where[] = "metadata->>'snapshot_date' <= :date_to";
            $params['date_to'] = $filters->dateRange->to->format('Y-m-d');
            $types['date_to'] = ParameterType::STRING;
        }

        // BETWEEN form when both bounds are set — lets the SQL stay literal
        // for the "scoped to month X" case the test pins, and keeps the
        // planner's range-scan estimate simpler.
        if ($filters->dateRange->from !== null && $filters->dateRange->to !== null) {
            array_pop($where);
            array_pop($where);
            $where[] = "metadata->>'snapshot_date' BETWEEN :date_from AND :date_to";
        }

        $whereClause = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        // Recency-biased ORDER BY only when the user didn't pin a date window.
        // GREATEST(0, ...) clamps future-dated rows (just in case) to no bonus.
        //
        // The cosine-distance subexpression MUST be parenthesised before the
        // `+` addition. Postgres parses `<=>` with low precedence, so without
        // parens `embedding <=> CAST(...) + 0.10 * decay` groups as
        // `embedding <=> (CAST(...) + 0.10 * decay)` → vector + numeric type
        // error at query time.
        $lambda = self::RECENCY_BIAS_LAMBDA_PER_30_DAYS;
        $orderBy = $hasExplicitDateFilter
            ? 'embedding <=> CAST(:query_vec AS vector)'
            : "(embedding <=> CAST(:query_vec AS vector)) "
                . "+ {$lambda} * GREATEST(0, (CURRENT_DATE - (metadata->>'snapshot_date')::date) / 30.0)";

        $sql = <<<SQL
            SELECT
                metadata->>'publication_id' AS publication_id,
                metadata->>'screen_name'    AS screen_name,
                metadata->>'snapshot_date'  AS snapshot_date,
                metadata->>'url'            AS url,
                metadata->>'avatar_url'     AS avatar_url,
                COALESCE(metadata->>'_text', metadata->>'content', '') AS text,
                COALESCE((metadata->>'reposts')::int, 0) AS reposts,
                COALESCE((metadata->>'likes')::int, 0)   AS likes,
                COALESCE((metadata->>'replies')::int, 0) AS replies,
                (embedding <=> CAST(:query_vec AS vector)) AS distance
            FROM {$this->table()}
            {$whereClause}
            ORDER BY {$orderBy}
            LIMIT :limit
            SQL;

        $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();

        return array_map(
            static fn (array $r): RetrievedHit => new RetrievedHit(
                publicationId: (string) ($r['publication_id'] ?? ''),
                screenName: (string) ($r['screen_name'] ?? ''),
                snapshotDate: (string) ($r['snapshot_date'] ?? ''),
                url: (string) ($r['url'] ?? ''),
                text: (string) ($r['text'] ?? ''),
                reposts: (int) ($r['reposts'] ?? 0),
                likes: (int) ($r['likes'] ?? 0),
                distance: max(0.0, (float) ($r['distance'] ?? 1.0)),
                replies: (int) ($r['replies'] ?? 0),
                avatarUrl: isset($r['avatar_url']) && $r['avatar_url'] !== '' ? (string) $r['avatar_url'] : null,
            ),
            $rows,
        );
    }

    private function table(): string
    {
        return self::TABLE;
    }
}

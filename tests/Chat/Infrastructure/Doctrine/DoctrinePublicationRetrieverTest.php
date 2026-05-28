<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\Doctrine;

use App\Chat\Domain\Query\DateRange;
use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Infrastructure\Doctrine\DoctrinePublicationRetriever;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\VectorizerInterface;
use Symfony\AI\Store\Document\EmbeddableDocumentInterface;

final class DoctrinePublicationRetrieverTest extends TestCase
{
    public function testUnfilteredQueryHitsAllRowsAndOrdersByDistance(): void
    {
        $conn = new RecordingConnection([
            ['publication_id' => 'at://p1', 'screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-03-04', 'url' => 'u1', 'text' => 't1', 'reposts' => 1, 'likes' => 2, 'distance' => 0.10],
            ['publication_id' => 'at://p2', 'screen_name' => 'liberation.fr', 'snapshot_date' => '2025-03-05', 'url' => 'u2', 'text' => 't2', 'reposts' => 3, 'likes' => 4, 'distance' => 0.20],
        ]);

        $retriever = new DoctrinePublicationRetriever($conn, new FixedVectorizer([0.1, 0.2, 0.3]));
        $hits = $retriever->retrieve('macron', 8, new QueryFilters());

        // No WHERE on metadata; just ORDER BY embedding <=>, LIMIT k.
        // ("screen_name" / "snapshot_date" appear in SELECT — we check WHERE-specific phrases.)
        self::assertStringNotContainsString("metadata->>'screen_name' IN", $conn->lastSql);
        self::assertStringNotContainsString("metadata->>'snapshot_date' >=", $conn->lastSql);
        self::assertStringNotContainsString("metadata->>'snapshot_date' BETWEEN", $conn->lastSql);
        // Cosine sort term must appear in ORDER BY in either bare or
        // parenthesised form (parenthesised when the recency bias is added).
        self::assertMatchesRegularExpression(
            '/ORDER\s+BY\s+\(?embedding\s*<=>/s',
            $conn->lastSql,
        );
        self::assertSame(8, $conn->lastParams['limit'] ?? null);

        self::assertCount(2, $hits);
        self::assertSame('at://p1', $hits[0]->publicationId);
        self::assertSame(0.10, $hits[0]->distance);
        self::assertSame('at://p2', $hits[1]->publicationId);
    }

    public function testScreenNameFilterIsPushedIntoSqlWhereClause(): void
    {
        $conn = new RecordingConnection([
            ['publication_id' => 'at://p1', 'screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-03-04', 'url' => 'u1', 'text' => 't1', 'reposts' => 0, 'likes' => 0, 'distance' => 0.30],
        ]);
        $retriever = new DoctrinePublicationRetriever($conn, new FixedVectorizer([0.0]));

        $hits = $retriever->retrieve('q', 8, new QueryFilters(screenNames: ['lemonde.fr', 'mediapart.fr']));

        // WHERE pushes the screen_name filter into SQL so pgvector ranks
        // ONLY rows that match the outlet, instead of post-filtering K candidates.
        self::assertStringContainsString("metadata->>'screen_name'", $conn->lastSql);
        self::assertStringContainsString('IN (', $conn->lastSql);
        // Limit is still K (not 4K) because no over-fetch is needed once the filter is in SQL.
        self::assertSame(8, $conn->lastParams['limit'] ?? null);
        self::assertSame(['lemonde.fr', 'mediapart.fr'], $conn->lastParams['screen_names'] ?? null);

        self::assertCount(1, $hits);
        self::assertSame('lemonde.fr', $hits[0]->screenName);
    }

    public function testDateRangeFilterIsPushedIntoSqlWhereClause(): void
    {
        $conn = new RecordingConnection([]);
        $retriever = new DoctrinePublicationRetriever($conn, new FixedVectorizer([0.0]));

        $retriever->retrieve('q', 8, new QueryFilters(
            dateRange: new DateRange(
                from: new \DateTimeImmutable('2025-03-01'),
                to: new \DateTimeImmutable('2025-03-31'),
            ),
        ));

        self::assertStringContainsString("metadata->>'snapshot_date'", $conn->lastSql);
        self::assertStringContainsString('BETWEEN', $conn->lastSql);
        self::assertSame('2025-03-01', $conn->lastParams['date_from'] ?? null);
        self::assertSame('2025-03-31', $conn->lastParams['date_to'] ?? null);
    }

    public function testEmbedsQueryViaInjectedVectorizer(): void
    {
        $vectorizer = new FixedVectorizer([0.5, -0.25]);
        $conn = new RecordingConnection([]);
        $retriever = new DoctrinePublicationRetriever($conn, $vectorizer);

        $retriever->retrieve('Macron en visite', 8, new QueryFilters());

        self::assertSame('Macron en visite', $vectorizer->lastInput);
        // pgvector accepts the array literal "[v1,v2,...]" form.
        self::assertSame('[0.5,-0.25]', $conn->lastParams['query_vec'] ?? null);
    }

    public function testUnfilteredQueryAppliesSoftRecencyBiasInOrderByClause(): void
    {
        // When no explicit date filter is set, the SQL must blend cosine
        // distance with a date-decay term so that a slightly-less-similar
        // recent doc can outrank a closer but stale doc. This is the
        // "nouvelles de la semaine" case: pure cosine over the whole corpus
        // doesn't prefer recency.
        $conn = new RecordingConnection([]);
        $retriever = new DoctrinePublicationRetriever($conn, new FixedVectorizer([0.0]));

        $retriever->retrieve('q', 8, new QueryFilters());

        // Decay is age (in days) divided by 30, scaled by lambda. Lambda
        // 0.10 keeps a 30-day-old doc only 0.10 worse than today's closest.
        self::assertStringContainsString('CURRENT_DATE', $conn->lastSql);
        self::assertStringContainsString("metadata->>'snapshot_date'", $conn->lastSql);
        // The decay belongs in the ORDER BY, not the WHERE — we still want
        // older docs to be retrievable, just deprioritised.
        self::assertMatchesRegularExpression(
            '/ORDER\s+BY[^L]*CURRENT_DATE/s',
            $conn->lastSql,
            'expected CURRENT_DATE to appear inside the ORDER BY expression',
        );
    }

    public function testUnfilteredCosineExpressionIsParenthesisedBeforeAdditionOperator(): void
    {
        // Regression: Postgres parses `<=>` with low precedence, so
        //     embedding <=> CAST(:v AS vector) + 0.10 * decay
        // groups as `embedding <=> (CAST + 0.10*decay)` → vector + numeric
        // type error. The decay must be added to the PARENTHESISED cosine
        // distance, not appended after the cast.
        $conn = new RecordingConnection([]);
        $retriever = new DoctrinePublicationRetriever($conn, new FixedVectorizer([0.0]));

        $retriever->retrieve('q', 8, new QueryFilters());

        // Pin the parenthesisation inside the ORDER BY clause specifically
        // (the SELECT clause also parenthesises the cosine expression to
        // alias it as `distance`; that's not what we need to check here).
        self::assertMatchesRegularExpression(
            '/ORDER\s+BY\s+\(embedding\s*<=>\s*CAST\(:query_vec\s+AS\s+vector\)\)\s*\+/s',
            $conn->lastSql,
            'cosine distance subexpression must be parenthesised in ORDER BY before the recency-decay addition',
        );
    }

    public function testExplicitDateFilterSuppressesRecencyBias(): void
    {
        // When the user already pinned a date window, an extra date-decay
        // term would skew results within that window (and is redundant).
        // The ORDER BY should be the bare cosine expression in that case.
        $conn = new RecordingConnection([]);
        $retriever = new DoctrinePublicationRetriever($conn, new FixedVectorizer([0.0]));

        $retriever->retrieve('q', 8, new QueryFilters(
            dateRange: new DateRange(
                from: new \DateTimeImmutable('2025-03-01'),
                to: new \DateTimeImmutable('2025-03-31'),
            ),
        ));

        self::assertStringNotContainsString('CURRENT_DATE', $conn->lastSql);
    }

    public function testEmptyScreenNamesArrayDoesNotEmitWhereClause(): void
    {
        // Regression: an empty list mustn't degenerate into `IN ()` (SQL error).
        $conn = new RecordingConnection([]);
        $retriever = new DoctrinePublicationRetriever($conn, new FixedVectorizer([0.0]));

        $retriever->retrieve('q', 8, new QueryFilters(screenNames: []));

        self::assertStringNotContainsString("metadata->>'screen_name' IN", $conn->lastSql);
    }
}

/** @internal */
final class RecordingConnection extends Connection
{
    public string $lastSql = '';
    /** @var array<string, mixed> */
    public array $lastParams = [];

    /** @param list<array<string, mixed>> $rows */
    public function __construct(private readonly array $rows)
    {
        // bypass parent constructor — we override the methods we use
    }

    public function executeQuery(string $sql, array $params = [], $types = [], ?\Doctrine\DBAL\Cache\QueryCacheProfile $qcp = null): Result
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;

        return new InMemoryResult($this->rows);
    }
}

/** @internal */
final class InMemoryResult extends Result
{
    /** @param list<array<string, mixed>> $rows */
    public function __construct(private array $rows)
    {
        // bypass parent constructor
    }

    public function fetchAllAssociative(): array
    {
        return $this->rows;
    }
}

/** @internal */
final class FixedVectorizer implements VectorizerInterface
{
    public ?string $lastInput = null;

    /** @param list<float> $components */
    public function __construct(private readonly array $components)
    {
    }

    public function vectorize(string|\Stringable|EmbeddableDocumentInterface|array $values, array $options = []): Vector
    {
        $this->lastInput = \is_string($values) ? $values : (string) $values;

        return new Vector($this->components);
    }
}

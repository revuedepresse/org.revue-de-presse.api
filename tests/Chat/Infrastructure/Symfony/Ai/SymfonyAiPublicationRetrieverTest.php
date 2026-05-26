<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Domain\Query\DateRange;
use App\Chat\Domain\Query\QueryFilters;
use App\Chat\Infrastructure\Symfony\Ai\SymfonyAiPublicationRetriever;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Vector\Vector;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\RetrieverInterface;

final class SymfonyAiPublicationRetrieverTest extends TestCase
{
    public function testMapsVectorDocumentMetadataToRetrievedHit(): void
    {
        $doc = $this->vectorDocument(
            id: 'at://pub-1',
            score: 0.92,
            metadata: [
                'snapshot_date' => '2025-03-04',
                'screen_name' => 'lemonde.fr',
                'url' => 'https://bsky.app/profile/lemonde.fr/post/abc',
                'reposts' => 73,
                'likes' => 169,
            ],
        );
        $retriever = new SymfonyAiPublicationRetriever(new ArrayRetriever([$doc]));

        $hits = $retriever->retrieve('q', 8, new QueryFilters());

        self::assertCount(1, $hits);
        self::assertSame('at://pub-1', $hits[0]->publicationId);
        self::assertSame('lemonde.fr', $hits[0]->screenName);
        self::assertSame('2025-03-04', $hits[0]->snapshotDate);
        self::assertSame('https://bsky.app/profile/lemonde.fr/post/abc', $hits[0]->url);
        self::assertSame(73, $hits[0]->reposts);
        self::assertSame(169, $hits[0]->likes);
        // score 0.92 → distance ≈ 0.08
        self::assertEqualsWithDelta(0.08, $hits[0]->distance, 0.001);
    }

    public function testScreenNameFilterDropsNonMatchingHits(): void
    {
        $docs = [
            $this->vectorDocument(id: 'p1', score: 0.9, metadata: ['screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-03-04']),
            $this->vectorDocument(id: 'p2', score: 0.8, metadata: ['screen_name' => 'mediapart.fr', 'snapshot_date' => '2025-03-04']),
            $this->vectorDocument(id: 'p3', score: 0.7, metadata: ['screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-03-04']),
        ];
        $retriever = new SymfonyAiPublicationRetriever(new ArrayRetriever($docs));

        $filters = new QueryFilters(screenNames: ['lemonde.fr']);
        $hits = $retriever->retrieve('q', 8, $filters);

        self::assertSame(['p1', 'p3'], array_map(fn ($h) => $h->publicationId, $hits));
    }

    public function testDateRangeFilterClampsBeforeAndAfterBounds(): void
    {
        $docs = [
            $this->vectorDocument(id: 'p-too-old', score: 0.9, metadata: ['screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-02-28']),
            $this->vectorDocument(id: 'p-inside', score: 0.85, metadata: ['screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-03-15']),
            $this->vectorDocument(id: 'p-too-new', score: 0.8, metadata: ['screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-04-01']),
        ];
        $retriever = new SymfonyAiPublicationRetriever(new ArrayRetriever($docs));

        $filters = new QueryFilters(dateRange: new DateRange(
            from: new \DateTimeImmutable('2025-03-01'),
            to: new \DateTimeImmutable('2025-03-31'),
        ));
        $hits = $retriever->retrieve('q', 8, $filters);

        self::assertSame(['p-inside'], array_map(fn ($h) => $h->publicationId, $hits));
    }

    public function testFiltersOverRetrieveByFourXThenTrimToK(): void
    {
        // 30 docs total; without filter, K=2 → first 2.
        $docs = [];
        for ($i = 1; $i <= 30; ++$i) {
            $docs[] = $this->vectorDocument(
                id: "p{$i}",
                score: 1 - $i / 100,
                metadata: ['screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-03-04'],
            );
        }
        $upstream = new ArrayRetriever($docs);
        $retriever = new SymfonyAiPublicationRetriever($upstream);

        // No filter: limit=k=2.
        $retriever->retrieve('q', 2, new QueryFilters());
        self::assertSame(2, $upstream->lastLimit);

        // With filter: limit=4k=8.
        $retriever->retrieve('q', 2, new QueryFilters(screenNames: ['lemonde.fr']));
        self::assertSame(8, $upstream->lastLimit);
    }

    public function testMetadataPublicationIdTakesPrecedenceOverRowId(): void
    {
        // After the UUIDv5 row-id migration, the embedder stores the
        // at-proto URI in metadata.publication_id; the row id itself is a
        // UUIDv5 hash. The retriever must surface the original URI, not
        // the hash, to keep citations stable.
        $doc = $this->vectorDocument(
            id: '5ee73f5d-45ff-52d3-9994-fe837432ed22',
            score: 0.9,
            metadata: [
                'publication_id' => 'at://did:plc:abc/app.bsky.feed.post/3lj',
                'screen_name' => 'lemonde.fr',
                'snapshot_date' => '2025-03-04',
            ],
        );
        $retriever = new SymfonyAiPublicationRetriever(new ArrayRetriever([$doc]));

        $hits = $retriever->retrieve('q', 1, new QueryFilters());

        self::assertSame('at://did:plc:abc/app.bsky.feed.post/3lj', $hits[0]->publicationId);
    }

    public function testRowIdIsUsedAsFallbackWhenMetadataLacksPublicationId(): void
    {
        // Legacy rows written before the UUIDv5 migration won't have
        // metadata.publication_id; the retriever falls back to the row id.
        $doc = $this->vectorDocument(
            id: 'at://legacy-row-id',
            score: 0.9,
            metadata: [
                'screen_name' => 'lemonde.fr',
                'snapshot_date' => '2025-03-04',
            ],
        );
        $retriever = new SymfonyAiPublicationRetriever(new ArrayRetriever([$doc]));

        $hits = $retriever->retrieve('q', 1, new QueryFilters());

        self::assertSame('at://legacy-row-id', $hits[0]->publicationId);
    }

    public function testNullScoreYieldsDistanceOne(): void
    {
        $doc = $this->vectorDocument(id: 'p1', score: null, metadata: ['screen_name' => 'lemonde.fr', 'snapshot_date' => '2025-03-04']);
        $retriever = new SymfonyAiPublicationRetriever(new ArrayRetriever([$doc]));

        $hits = $retriever->retrieve('q', 1, new QueryFilters());

        self::assertSame(1.0, $hits[0]->distance);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function vectorDocument(string $id, ?float $score, array $metadata): VectorDocument
    {
        return new VectorDocument(
            id: $id,
            vector: new Vector([0.0]),
            metadata: new Metadata($metadata),
            score: $score,
        );
    }
}

/** @internal */
final class ArrayRetriever implements RetrieverInterface
{
    public ?int $lastLimit = null;

    /** @param list<VectorDocument> $documents */
    public function __construct(private readonly array $documents)
    {
    }

    public function retrieve(string $query, array $options = []): iterable
    {
        $this->lastLimit = isset($options['limit']) && is_int($options['limit']) ? $options['limit'] : null;
        $limit = $this->lastLimit ?? \PHP_INT_MAX;

        return \array_slice($this->documents, 0, $limit);
    }
}

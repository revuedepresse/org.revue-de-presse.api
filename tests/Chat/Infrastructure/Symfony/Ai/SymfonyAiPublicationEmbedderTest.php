<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Application\PromptBuilder;
use App\Chat\Domain\Text\TextCleaner;
use App\Chat\Infrastructure\Symfony\Ai\SymfonyAiPublicationEmbedder;
use App\NewsReview\Domain\Model\HighlightDto;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\IndexerInterface;

final class SymfonyAiPublicationEmbedderTest extends TestCase
{
    public function testRowIdIsUuidV5HashAndOriginalAtUriIsPreservedInMetadata(): void
    {
        // Regression: the underlying Postgres bridge schema is `id UUID
        // PRIMARY KEY`; passing the at-proto URI directly used to fail with
        // `invalid input syntax for type uuid`. The embedder now hashes the
        // URI into a deterministic UUIDv5 and keeps the URI in metadata.
        $indexer = new RecordingIndexer();
        $embedder = new SymfonyAiPublicationEmbedder($indexer, new TextCleaner(), new PromptBuilder());

        $atUri = 'at://did:plc:uj54w5cel35z7qy3hk5zch4h/app.bsky.feed.post/3ljovqmcjn224';
        $expectedRowId = '5ee73f5d-45ff-52d3-9994-fe837432ed22';

        $embedder->embedBatch([$this->highlight($atUri)]);

        self::assertCount(1, $indexer->indexed);
        $doc = $indexer->indexed[0];
        self::assertSame($expectedRowId, $doc->getId(), 'row id must be UUIDv5 of the at-URI');

        $meta = $doc->getMetadata()->getArrayCopy();
        self::assertSame($atUri, $meta['publication_id'] ?? null, 'original at-URI must live in metadata');
    }

    public function testRowIdForIsDeterministicAcrossCalls(): void
    {
        $atUri = 'at://pub-1';
        self::assertSame(
            SymfonyAiPublicationEmbedder::rowIdFor($atUri),
            SymfonyAiPublicationEmbedder::rowIdFor($atUri),
        );
        // Frozen fixture — if this changes, every row in pgvector is orphaned.
        self::assertSame(
            'b95896e2-1c94-59fc-9aea-7be8665e29db',
            SymfonyAiPublicationEmbedder::rowIdFor($atUri),
        );
    }

    public function testCleanedTextIsPreservedInMetadataForCitationReadBack(): void
    {
        // The pgvector store only persists id + metadata + embedding — the
        // TextDocument content is consumed by the vectorizer and discarded.
        // To surface the original text in RetrievedHit::text (so the prompt
        // and the citations panel have something to quote), the embedder
        // must echo the cleaned text into metadata.content.
        $indexer = new RecordingIndexer();
        $embedder = new SymfonyAiPublicationEmbedder($indexer, new TextCleaner(), new PromptBuilder());

        $embedder->embedBatch([$this->highlight('at://did:plc:abc/app.bsky.feed.post/123')]);

        $meta = $indexer->indexed[0]->getMetadata()->getArrayCopy();
        self::assertArrayHasKey('content', $meta);
        // Cleaned, header-free — the screen_name and date are already
        // separate metadata fields. The retriever-side formatter quotes the
        // text directly under "[N] lemonde.fr — 2025-03-04 — N reposts".
        self::assertSame('Donald Trump gèle l’aide militaire à l’Ukraine', $meta['content']);
    }

    public function testEmptyBatchIsANoop(): void
    {
        $indexer = new RecordingIndexer();
        $embedder = new SymfonyAiPublicationEmbedder($indexer, new TextCleaner(), new PromptBuilder());

        $embedder->embedBatch([]);

        self::assertSame([], $indexer->indexed);
    }

    private function highlight(string $publicationId): HighlightDto
    {
        return new HighlightDto(
            publicationId: $publicationId,
            screenName: 'lemonde.fr',
            text: 'Donald Trump gèle l’aide militaire à l’Ukraine',
            date: new \DateTimeImmutable('2025-03-04'),
            url: 'https://bsky.app/profile/lemonde.fr/post/abc',
            reposts: 73,
            likes: 169,
            replies: 5,
            avatarUrl: 'https://cdn.bsky.app/img/avatar/lemonde.jpg',
        );
    }
}

/** @internal */
final class RecordingIndexer implements IndexerInterface
{
    /** @var list<TextDocument> */
    public array $indexed = [];

    public function index(string|iterable|object $input, array $options = []): void
    {
        $documents = is_iterable($input) ? $input : [$input];
        foreach ($documents as $document) {
            $this->indexed[] = $document;
        }
    }
}

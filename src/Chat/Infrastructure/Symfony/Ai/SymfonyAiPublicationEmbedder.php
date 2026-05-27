<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Symfony\Ai;

use App\Chat\Application\Port\PublicationEmbedder;
use App\Chat\Application\PromptBuilder;
use App\Chat\Domain\Text\TextCleaner;
use App\NewsReview\Domain\Model\HighlightDto;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\IndexerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Adapter wiring symfony/ai-store's IndexerInterface (vectorizer →
 * PostgresStore) to our PublicationEmbedder port.
 *
 * The Postgres bridge hardcodes the row `id` column as `UUID PRIMARY KEY`
 * (see vendor/symfony/ai-postgres-store/Store.php::setup()), but our
 * upstream publication ids are at-proto URIs like
 * `at://did:plc:.../app.bsky.feed.post/3lj...` — which Postgres rejects
 * with `invalid input syntax for type uuid`. We derive a deterministic
 * UUIDv5 from the at-URI for the row id, and keep the original URI in
 * metadata so the retriever can surface it intact. Re-runs upsert by the
 * same UUIDv5 (same at-URI → same UUID).
 */
final class SymfonyAiPublicationEmbedder implements PublicationEmbedder
{
    /**
     * Namespace UUID for hashing at-proto publication ids into row keys.
     * Generated once via:
     *   Uuid::v5(Uuid::fromString(Uuid::NAMESPACE_DNS),
     *            'revue-de-presse.org/chat-publication')
     * and frozen here. MUST NOT CHANGE — rotating it orphans every existing
     * row in the pgvector store.
     */
    private const PUBLICATION_ID_NAMESPACE = '1e75b0d1-7c16-57be-91b7-98b70c8007c7';

    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly TextCleaner $textCleaner,
        private readonly PromptBuilder $promptBuilder,
    ) {
    }

    /**
     * @internal Exposed for the retriever's reverse-lookup pathway.
     */
    public static function rowIdFor(string $publicationId): string
    {
        return (string) Uuid::v5(Uuid::fromString(self::PUBLICATION_ID_NAMESPACE), $publicationId);
    }

    /**
     * @param list<HighlightDto> $highlights
     */
    public function embedBatch(array $highlights): void
    {
        if ($highlights === []) {
            return;
        }

        $documents = array_map(
            fn (HighlightDto $h): TextDocument => $this->toDocument($h),
            $highlights,
        );
        $this->indexer->index($documents);
    }

    private function toDocument(HighlightDto $h): TextDocument
    {
        $cleaned = $this->textCleaner->clean($h->text);
        $header = $h->screenName . ' — ' . $this->promptBuilder->longFrenchDate($h->date);
        $content = $header . "\n" . $cleaned;

        $metadata = new Metadata([
            // Keep the at-proto URI in metadata so the retriever can surface
            // it back as RetrievedHit::publicationId (the row id is now a
            // UUIDv5 hash, not the URI itself).
            'publication_id' => $h->publicationId,
            'snapshot_date' => $h->date->format('Y-m-d'),
            'screen_name' => $h->screenName,
            'url' => $h->url,
            'reposts' => $h->reposts,
            'likes' => $h->likes,
            'replies' => $h->replies,
            'avatar_url' => $h->avatarUrl,
            // Echo the cleaned text into metadata so the retriever can surface
            // it as RetrievedHit::text. The Postgres bridge schema is
            // (id, metadata, embedding) — the TextDocument content is
            // consumed by the vectorizer and discarded, so without this we
            // lose the original text on read-back, and the prompt gets a
            // hit list with empty quoted bodies. Stored as just the cleaned
            // text (no screen_name/date header — those are separate fields
            // that PromptBuilder formats around the quote).
            'content' => $cleaned,
        ]);

        return new TextDocument(
            id: self::rowIdFor($h->publicationId),
            content: $content,
            metadata: $metadata,
        );
    }
}

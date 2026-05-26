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

/**
 * Adapter wiring symfony/ai-store's IndexerInterface (vectorizer →
 * PostgresStore) to our PublicationEmbedder port. The `id` of each
 * TextDocument is the upstream publication_id (an at-proto URI), so
 * the underlying PostgresStore upserts by id on re-runs.
 */
final class SymfonyAiPublicationEmbedder implements PublicationEmbedder
{
    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly TextCleaner $textCleaner,
        private readonly PromptBuilder $promptBuilder,
    ) {
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
            'snapshot_date' => $h->date->format('Y-m-d'),
            'screen_name' => $h->screenName,
            'url' => $h->url,
            'reposts' => $h->reposts,
            'likes' => $h->likes,
            'replies' => $h->replies,
            'avatar_url' => $h->avatarUrl,
        ]);

        return new TextDocument(id: $h->publicationId, content: $content, metadata: $metadata);
    }
}

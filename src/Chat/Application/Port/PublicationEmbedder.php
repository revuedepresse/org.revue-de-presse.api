<?php
declare(strict_types=1);

namespace App\Chat\Application\Port;

use App\NewsReview\Domain\Model\HighlightDto;

/**
 * Indexes one or more publications into the vector store. The concrete
 * implementation owns batching and provider calls; callers only see the
 * domain shape (HighlightDto from NewsReview).
 */
interface PublicationEmbedder
{
    /**
     * @param list<HighlightDto> $highlights
     */
    public function embedBatch(array $highlights): void;
}

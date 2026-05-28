<?php
declare(strict_types=1);

namespace App\Chat\Domain\Retrieval;

/**
 * One retrieved publication from the vector store, denormalised to
 * the fields the prompt + citations panel need. Distance is cosine,
 * 0 (identical) → 1 (orthogonal) → 2 (opposite).
 */
final readonly class RetrievedHit
{
    public function __construct(
        public string $publicationId,
        public string $screenName,
        public string $snapshotDate, // YYYY-MM-DD
        public string $url,
        public string $text,
        public int $reposts,
        public int $likes,
        public float $distance,
        public int $replies = 0,
        public ?string $avatarUrl = null,
    ) {
    }
}

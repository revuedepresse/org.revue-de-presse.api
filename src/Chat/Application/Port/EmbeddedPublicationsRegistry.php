<?php
declare(strict_types=1);

namespace App\Chat\Application\Port;

/**
 * Lookup port for the EmbedSnapshots command's idempotency check: which of
 * these publication ids are already present in the vector store? Anything
 * the registry reports as existing is skipped, so the embedder never
 * re-pays the provider HTTP cost for a publication we've already indexed.
 */
interface EmbeddedPublicationsRegistry
{
    /**
     * @param list<string> $publicationIds at-proto URIs
     *
     * @return list<string> Subset of $publicationIds already embedded
     */
    public function existingPublicationIds(array $publicationIds): array;
}

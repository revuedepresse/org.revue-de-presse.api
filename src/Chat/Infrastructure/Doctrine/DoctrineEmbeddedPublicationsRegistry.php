<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Doctrine;

use App\Chat\Application\Port\EmbeddedPublicationsRegistry;
use App\Chat\Infrastructure\Symfony\Ai\SymfonyAiPublicationEmbedder;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Implements EmbeddedPublicationsRegistry by querying the pgvector store's
 * row-id column directly. Row ids are the UUIDv5 hash of the at-proto URI
 * (see SymfonyAiPublicationEmbedder::rowIdFor()), so we hash each input,
 * SELECT the existing ones, and map back to the original URIs.
 *
 * Hardcoded against `chat_publication_embedding` — MUST match the
 * `store.postgres.chat_publications.table_name` in config/packages/ai.yaml.
 */
final class DoctrineEmbeddedPublicationsRegistry implements EmbeddedPublicationsRegistry
{
    private const TABLE = 'chat_publication_embedding';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function existingPublicationIds(array $publicationIds): array
    {
        if ($publicationIds === []) {
            return [];
        }

        $byRowId = [];
        foreach ($publicationIds as $publicationId) {
            $byRowId[SymfonyAiPublicationEmbedder::rowIdFor($publicationId)] = $publicationId;
        }

        $rows = $this->connection->executeQuery(
            \sprintf('SELECT id FROM %s WHERE id IN (?)', self::TABLE),
            [array_keys($byRowId)],
            [ArrayParameterType::STRING],
        )->fetchFirstColumn();

        $existing = [];
        foreach ($rows as $rowId) {
            $rowId = (string) $rowId;
            if (isset($byRowId[$rowId])) {
                $existing[] = $byRowId[$rowId];
            }
        }

        return $existing;
    }
}

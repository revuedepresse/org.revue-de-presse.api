<?php
declare(strict_types=1);

namespace App\Chat\Application;

use App\Chat\Domain\Retrieval\RetrievedHit;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Pulls the model's [n] markers out of its response and resolves
 * each to the matching publication id from the retrieval hit list.
 * Out-of-range indices are logged and dropped silently.
 */
final class CitationExtractor
{
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param list<RetrievedHit> $hits
     * @return list<string> publication IDs in order of first appearance
     */
    public function extract(string $assistantOutput, array $hits): array
    {
        if (preg_match_all('/\[(\d+)\]/', $assistantOutput, $matches) !== 1 && $matches[1] === []) {
            return [];
        }

        $seen = [];
        foreach ($matches[1] as $raw) {
            $n = (int) $raw;
            if ($n < 1 || $n > count($hits)) {
                $this->logger->warning('chat.citation.invalid_index', ['index' => $n, 'hits' => count($hits)]);
                continue;
            }
            $publicationId = $hits[$n - 1]->publicationId;
            $seen[$publicationId] = true;
        }

        return array_keys($seen);
    }
}

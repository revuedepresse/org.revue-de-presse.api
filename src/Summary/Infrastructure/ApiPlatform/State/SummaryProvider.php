<?php
declare(strict_types=1);

namespace App\Summary\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Summary\Domain\DailySummaryRepository;
use App\Summary\Infrastructure\ApiPlatform\Resource\Summary;

/**
 * @implements ProviderInterface<Summary>
 */
final class SummaryProvider implements ProviderInterface
{
    public function __construct(private readonly DailySummaryRepository $repository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Summary
    {
        $date = $uriVariables['date'] ?? null;
        if (!\is_string($date) || $date === '') {
            return null;
        }

        try {
            $summary = $this->repository->read($date);
        } catch (\InvalidArgumentException) {
            // The filesystem repository throws on malformed dates as a
            // path-traversal defence. API Platform should surface a 404,
            // not a 500 — to the client, an unparseable date is just
            // "no resource exists at that identifier".
            return null;
        }

        if ($summary === null) {
            return null;
        }

        return new Summary(date: $summary->date, markdown: $summary->markdown);
    }
}

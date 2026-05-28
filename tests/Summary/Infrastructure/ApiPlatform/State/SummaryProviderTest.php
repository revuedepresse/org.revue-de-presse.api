<?php
declare(strict_types=1);

namespace App\Tests\Summary\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Get;
use App\Summary\Domain\DailySummary;
use App\Summary\Domain\DailySummaryRepository;
use App\Summary\Infrastructure\ApiPlatform\Resource\Summary;
use App\Summary\Infrastructure\ApiPlatform\State\SummaryProvider;
use PHPUnit\Framework\TestCase;

final class SummaryProviderTest extends TestCase
{
    public function testReturnsSummaryDtoWhenDateExists(): void
    {
        $repo = new InMemoryRepo([
            '2026-05-26' => new DailySummary('2026-05-26', "## Résumé\nUne synthèse.\n"),
        ]);
        $provider = new SummaryProvider($repo);

        $result = $provider->provide(new Get(uriTemplate: '/days/{date}/summary'), ['date' => '2026-05-26']);

        self::assertInstanceOf(Summary::class, $result);
        self::assertSame('2026-05-26', $result->date);
        self::assertSame("## Résumé\nUne synthèse.\n", $result->markdown);
    }

    public function testReturnsNullWhenDateIsMissingTriggers404(): void
    {
        // API Platform interprets a null Provider return as 404 for item ops.
        $provider = new SummaryProvider(new InMemoryRepo([]));
        $result = $provider->provide(new Get(uriTemplate: '/days/{date}/summary'), ['date' => '2099-01-01']);
        self::assertNull($result);
    }

    public function testReturnsNullWhenDateIsMalformedRatherThanThrowing500(): void
    {
        // The repository's date validator throws InvalidArgumentException for
        // bad date strings (defence against path-traversal). The provider
        // catches it and 404s — the client just sees "no summary for that
        // identifier", which is the correct semantic.
        $provider = new SummaryProvider(new InMemoryRepo([]));
        $result = $provider->provide(new Get(uriTemplate: '/days/{date}/summary'), ['date' => '../../etc/passwd']);
        self::assertNull($result);
    }
}

/** @internal */
final class InMemoryRepo implements DailySummaryRepository
{
    /** @param array<string, DailySummary> $byDate */
    public function __construct(private readonly array $byDate)
    {
    }

    public function read(string $date): ?DailySummary
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new \InvalidArgumentException("Invalid date: {$date}");
        }

        return $this->byDate[$date] ?? null;
    }

    public function save(DailySummary $summary): void
    {
        throw new \BadMethodCallException('read-only test fixture');
    }

    public function exists(string $date): bool
    {
        return isset($this->byDate[$date]);
    }
}

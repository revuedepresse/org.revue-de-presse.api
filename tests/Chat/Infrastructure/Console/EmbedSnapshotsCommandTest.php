<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\Console;

use App\Chat\Application\Port\EmbeddedPublicationsRegistry;
use App\Chat\Application\Port\PublicationEmbedder;
use App\Chat\Infrastructure\Console\EmbedSnapshotsCommand;
use App\NewsReview\Domain\Model\HighlightDto;
use App\NewsReview\Domain\Snapshot\Filter\HighlightNormalizer;
use App\NewsReview\Domain\Snapshot\SnapshotReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class EmbedSnapshotsCommandTest extends TestCase
{
    public function testInvalidFromDateReturnsInvalid(): void
    {
        $tester = $this->newTester(new InMemorySnapshotReader([]), new RecordingEmbedder());
        $code = $tester->execute(['--from' => 'not-a-date']);
        self::assertSame(Command::INVALID, $code);
    }

    public function testFromAfterToReturnsInvalid(): void
    {
        $tester = $this->newTester(new InMemorySnapshotReader([]), new RecordingEmbedder());
        $code = $tester->execute(['--from' => '2026-03-10', '--to' => '2026-03-01']);
        self::assertSame(Command::INVALID, $code);
    }

    public function testSingleDateModeWalksOnlyThatDate(): void
    {
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [$this->snapshotRow('p1', '2025-03-04')],
            '2025-03-05' => [$this->snapshotRow('p2', '2025-03-05')],
        ]);
        $embedder = new RecordingEmbedder();
        $tester = $this->newTester($reader, $embedder);

        $code = $tester->execute(['--date' => '2025-03-04', '--throttle-seconds' => '0']);
        self::assertSame(Command::SUCCESS, $code);
        self::assertSame(1, $embedder->totalEmbedded());
        self::assertSame(['p1'], $embedder->embeddedIds());
    }

    public function testDryRunSkipsEmbedderCalls(): void
    {
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [$this->snapshotRow('p1', '2025-03-04'), $this->snapshotRow('p2', '2025-03-04')],
        ]);
        $embedder = new RecordingEmbedder();
        $tester = $this->newTester($reader, $embedder);

        $code = $tester->execute(['--date' => '2025-03-04', '--dry-run' => true, '--throttle-seconds' => '0']);
        self::assertSame(Command::SUCCESS, $code);
        self::assertSame(0, $embedder->batchCount(), 'embedder must never be called in dry-run');
        self::assertSame(0, $embedder->totalEmbedded());
    }

    public function testRangeModeWalksEveryDayInclusive(): void
    {
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [$this->snapshotRow('p1', '2025-03-04')],
            '2025-03-05' => [$this->snapshotRow('p2', '2025-03-05')],
            '2025-03-06' => [$this->snapshotRow('p3', '2025-03-06')],
        ]);
        $embedder = new RecordingEmbedder();
        $tester = $this->newTester($reader, $embedder);

        $code = $tester->execute([
            '--from' => '2025-03-04',
            '--to' => '2025-03-06',
            '--batch-size' => '1',
            '--throttle-seconds' => '0',
        ]);
        self::assertSame(Command::SUCCESS, $code);
        self::assertSame(3, $embedder->totalEmbedded());
        self::assertSame(['p1', 'p2', 'p3'], $embedder->embeddedIds());
    }

    public function testEmptySnapshotIsSkippedNotFailed(): void
    {
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [], // missing snapshot returns empty
            '2025-03-05' => [$this->snapshotRow('p1', '2025-03-05')],
        ]);
        $embedder = new RecordingEmbedder();
        $tester = $this->newTester($reader, $embedder);

        $code = $tester->execute(['--from' => '2025-03-04', '--to' => '2025-03-05', '--throttle-seconds' => '0']);
        self::assertSame(Command::SUCCESS, $code);
        self::assertSame(1, $embedder->totalEmbedded());
    }

    public function testEmbedderFailurePartialReturnsExitOne(): void
    {
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [$this->snapshotRow('p1', '2025-03-04')],
            '2025-03-05' => [$this->snapshotRow('p2', '2025-03-05')],
        ]);
        $embedder = new RecordingEmbedder(failOnDates: ['2025-03-04']);
        $tester = $this->newTester($reader, $embedder);

        $code = $tester->execute([
            '--from' => '2025-03-04',
            '--to' => '2025-03-05',
            '--batch-size' => '1',
            '--throttle-seconds' => '0',
        ]);
        // 2025-03-04 failed, 2025-03-05 succeeded → partial → exit 1.
        self::assertSame(1, $code);
    }

    public function testIdempotentReRunSkipsPublicationsAlreadyEmbedded(): void
    {
        // Idempotency: publications the registry reports as already present
        // in the pgvector store must be skipped — no provider HTTP call.
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [
                $this->snapshotRow('p1', '2025-03-04'),
                $this->snapshotRow('p2', '2025-03-04'),
                $this->snapshotRow('p3', '2025-03-04'),
            ],
        ]);
        $embedder = new RecordingEmbedder();
        $registry = new InMemoryEmbeddedPublicationsRegistry(['p1', 'p3']);
        $tester = $this->newTester($reader, $embedder, $registry);

        $code = $tester->execute(['--date' => '2025-03-04', '--throttle-seconds' => '0']);

        self::assertSame(Command::SUCCESS, $code);
        self::assertSame(['p2'], $embedder->embeddedIds(), 'p1 and p3 must not hit the embedder');
        self::assertSame(1, $embedder->totalEmbedded());
    }

    public function testFullySkippedBatchDoesNotCallEmbedderAtAll(): void
    {
        // Whole-batch skip: when the registry knows about every publication
        // in the batch, the embedder is not invoked even once (no empty
        // batches reach the provider).
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [
                $this->snapshotRow('p1', '2025-03-04'),
                $this->snapshotRow('p2', '2025-03-04'),
            ],
        ]);
        $embedder = new RecordingEmbedder();
        $registry = new InMemoryEmbeddedPublicationsRegistry(['p1', 'p2']);
        $tester = $this->newTester($reader, $embedder, $registry);

        $code = $tester->execute(['--date' => '2025-03-04', '--throttle-seconds' => '0']);

        self::assertSame(Command::SUCCESS, $code);
        self::assertSame(0, $embedder->batchCount(), 'embedder must not be called when every pub is already embedded');
    }

    public function testForceFlagBypassesTheRegistryAndReEmbedsEverything(): void
    {
        // --force opts out of the idempotency check (useful when the
        // embedding semantics change — e.g. new model, new chunking rule).
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [
                $this->snapshotRow('p1', '2025-03-04'),
                $this->snapshotRow('p2', '2025-03-04'),
            ],
        ]);
        $embedder = new RecordingEmbedder();
        $registry = new InMemoryEmbeddedPublicationsRegistry(['p1', 'p2']);
        $tester = $this->newTester($reader, $embedder, $registry);

        $code = $tester->execute([
            '--date' => '2025-03-04',
            '--throttle-seconds' => '0',
            '--force' => true,
        ]);

        self::assertSame(Command::SUCCESS, $code);
        self::assertSame(['p1', 'p2'], $embedder->embeddedIds(), '--force must re-embed even when registry says they exist');
    }

    public function testThrottleSecondsZeroMakesTheRunSubSecond(): void
    {
        // Regression: the throttle option must be honoured. With
        // --throttle-seconds=0, embedding 3 days back-to-back must NOT
        // take ≥ 2s (the default per-batch sleep). If the option ever
        // gets ignored, this test catches it because the run blows past
        // 2s wall time even on a fast CI box.
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [$this->snapshotRow('p1', '2025-03-04')],
            '2025-03-05' => [$this->snapshotRow('p2', '2025-03-05')],
            '2025-03-06' => [$this->snapshotRow('p3', '2025-03-06')],
        ]);
        $tester = $this->newTester($reader, new RecordingEmbedder());

        $start = microtime(true);
        $tester->execute([
            '--from' => '2025-03-04',
            '--to' => '2025-03-06',
            '--batch-size' => '1',
            '--throttle-seconds' => '0',
        ]);
        $elapsed = microtime(true) - $start;

        self::assertLessThan(1.0, $elapsed, 'throttle-seconds=0 must skip the per-batch sleep');
    }

    public function testAllDaysFailingReturnsExitTwo(): void
    {
        $reader = new InMemorySnapshotReader([
            '2025-03-04' => [$this->snapshotRow('p1', '2025-03-04')],
            '2025-03-05' => [$this->snapshotRow('p2', '2025-03-05')],
        ]);
        $embedder = new RecordingEmbedder(failOnDates: ['2025-03-04', '2025-03-05']);
        $tester = $this->newTester($reader, $embedder);

        $code = $tester->execute([
            '--from' => '2025-03-04',
            '--to' => '2025-03-05',
            '--batch-size' => '1',
            '--throttle-seconds' => '0',
        ]);
        self::assertSame(2, $code);
    }

    /**
     * @return array{
     *     publication_id: string,
     *     screen_name: string,
     *     text: string,
     *     date: string,
     *     reposts: int,
     *     likes: int,
     * }
     */
    private function snapshotRow(string $publicationId, string $date): array
    {
        return [
            'publication_id' => $publicationId,
            'screen_name' => 'lemonde.fr',
            'text' => "post {$publicationId}",
            'date' => $date,
            'reposts' => 1,
            'likes' => 1,
        ];
    }

    private function newTester(
        SnapshotReader $reader,
        PublicationEmbedder $embedder,
        ?EmbeddedPublicationsRegistry $registry = null,
    ): CommandTester {
        $command = new EmbedSnapshotsCommand(
            $reader,
            new HighlightNormalizer(),
            $embedder,
            $registry ?? new InMemoryEmbeddedPublicationsRegistry(),
        );

        return new CommandTester($command);
    }
}

// ----------------------------------------------------------------------------
// In-memory test doubles
// ----------------------------------------------------------------------------

/** @internal */
final class InMemorySnapshotReader implements SnapshotReader
{
    /**
     * @param array<string, list<array<string, mixed>>> $byDate
     */
    public function __construct(private readonly array $byDate)
    {
    }

    public function read(string $date): array
    {
        return $this->byDate[$date] ?? [];
    }
}

/** @internal Returns whichever publication_ids were seeded as "already embedded". */
final class InMemoryEmbeddedPublicationsRegistry implements EmbeddedPublicationsRegistry
{
    /** @param list<string> $existing */
    public function __construct(private readonly array $existing = [])
    {
    }

    public function existingPublicationIds(array $publicationIds): array
    {
        return array_values(array_intersect($publicationIds, $this->existing));
    }
}

/** @internal */
final class RecordingEmbedder implements PublicationEmbedder
{
    /** @var list<HighlightDto> */
    private array $embedded = [];

    private int $batches = 0;

    /** @param list<string> $failOnDates */
    public function __construct(private readonly array $failOnDates = [])
    {
    }

    public function embedBatch(array $highlights): void
    {
        ++$this->batches;
        if ($highlights === []) {
            return;
        }
        $batchDate = $highlights[0]->date->format('Y-m-d');
        if (\in_array($batchDate, $this->failOnDates, true)) {
            throw new \RuntimeException("simulated failure for {$batchDate}");
        }
        foreach ($highlights as $highlight) {
            $this->embedded[] = $highlight;
        }
    }

    public function totalEmbedded(): int
    {
        return \count($this->embedded);
    }

    public function batchCount(): int
    {
        return $this->batches;
    }

    /** @return list<string> */
    public function embeddedIds(): array
    {
        return array_map(fn (HighlightDto $h): string => $h->publicationId, $this->embedded);
    }
}

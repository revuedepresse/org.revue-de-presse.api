<?php
declare(strict_types=1);

namespace App\Tests\Chat\Infrastructure\Console;

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

        $code = $tester->execute(['--date' => '2025-03-04']);
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

        $code = $tester->execute(['--date' => '2025-03-04', '--dry-run' => true]);
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

        $code = $tester->execute(['--from' => '2025-03-04', '--to' => '2025-03-05']);
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
        ]);
        // 2025-03-04 failed, 2025-03-05 succeeded → partial → exit 1.
        self::assertSame(1, $code);
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

    private function newTester(SnapshotReader $reader, PublicationEmbedder $embedder): CommandTester
    {
        $command = new EmbedSnapshotsCommand($reader, new HighlightNormalizer(), $embedder);

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

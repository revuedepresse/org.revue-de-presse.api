<?php
declare(strict_types=1);

namespace App\Tests\Summary\Infrastructure\Console;

use App\Summary\Application\DailySummaryGeneratorInterface;
use App\Summary\Domain\DailySummary;
use App\Summary\Domain\DailySummaryRepository;
use App\Summary\Infrastructure\Console\GenerateDailySummariesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateDailySummariesCommandTest extends TestCase
{
    public function testDryRunDoesNotCallGeneratorOrRepository(): void
    {
        $generator = new RecordingGenerator(returns: new DailySummary('x', 'x'));
        $repo = new InMemoryRepository();
        $tester = $this->tester($generator, $repo);

        $code = $tester->execute([
            '--date' => '2025-03-04',
            '--dry-run' => true,
        ]);

        self::assertSame(0, $code);
        self::assertSame(0, $generator->callCount);
        self::assertSame([], $repo->saved);
    }

    public function testSkipsDateWhenSummaryAlreadyExists(): void
    {
        $generator = new RecordingGenerator(returns: new DailySummary('2025-03-04', 'x'));
        $repo = new InMemoryRepository();
        $repo->save(new DailySummary('2025-03-04', 'pre-existing'));

        $tester = $this->tester($generator, $repo);
        $code = $tester->execute([
            '--date' => '2025-03-04',
        ]);

        self::assertSame(0, $code);
        self::assertSame(0, $generator->callCount, 'generator must not run when summary exists');
        self::assertSame('pre-existing', $repo->read('2025-03-04')->markdown);
    }

    public function testForceRegeneratesEvenIfSummaryExists(): void
    {
        $generator = new RecordingGenerator(returns: new DailySummary('2025-03-04', 'fresh'));
        $repo = new InMemoryRepository();
        $repo->save(new DailySummary('2025-03-04', 'stale'));

        $tester = $this->tester($generator, $repo);
        $code = $tester->execute([
            '--date' => '2025-03-04',
            '--force' => true,
        ]);

        self::assertSame(0, $code);
        self::assertSame(1, $generator->callCount);
        self::assertSame('fresh', $repo->read('2025-03-04')->markdown);
    }

    public function testGeneratorReturningNullCountsAsNoSnapshotSkipNotFailure(): void
    {
        $generator = new RecordingGenerator(returns: null);
        $repo = new InMemoryRepository();
        $tester = $this->tester($generator, $repo);

        $code = $tester->execute([
            '--date' => '2025-03-04',
        ]);

        self::assertSame(0, $code, 'null return is a benign skip, exit 0');
        self::assertSame(1, $generator->callCount);
        self::assertSame([], $repo->saved);
    }

    public function testRejectsMissingDateOption(): void
    {
        $generator = new RecordingGenerator(returns: null);
        $repo = new InMemoryRepository();
        $tester = $this->tester($generator, $repo);

        $code = $tester->execute([]);

        self::assertSame(Command::INVALID, $code);
        self::assertStringContainsString('--date is required', $tester->getDisplay());
    }

    public function testRejectsMalformedDate(): void
    {
        $generator = new RecordingGenerator(returns: null);
        $repo = new InMemoryRepository();
        $tester = $this->tester($generator, $repo);

        $code = $tester->execute(['--date' => '2025-3-4']);

        self::assertSame(Command::INVALID, $code);
        self::assertStringContainsString('Not a valid YYYY-MM-DD date', $tester->getDisplay());
    }

    private function tester(DailySummaryGeneratorInterface $generator, DailySummaryRepository $repo): CommandTester
    {
        $cmd = new GenerateDailySummariesCommand($generator, $repo);
        $app = new Application();
        $app->addCommand($cmd);

        return new CommandTester($app->find('chat:generate-daily-summaries'));
    }
}

/** @internal Records what generate() was called with; returns the same fixture each time. */
final class RecordingGenerator implements DailySummaryGeneratorInterface
{
    /** @var list<string> */
    public array $dates = [];
    public int $callCount = 0;

    public function __construct(private readonly ?DailySummary $returns)
    {
    }

    public function generate(string $date): ?DailySummary
    {
        $this->callCount++;
        $this->dates[] = $date;

        return $this->returns;
    }
}

/** @internal */
final class InMemoryRepository implements DailySummaryRepository
{
    /** @var array<string, DailySummary> */
    public array $saved = [];

    public function read(string $date): ?DailySummary
    {
        return $this->saved[$date] ?? null;
    }

    public function save(DailySummary $summary): void
    {
        $this->saved[$summary->date] = $summary;
    }

    public function exists(string $date): bool
    {
        return isset($this->saved[$date]);
    }
}

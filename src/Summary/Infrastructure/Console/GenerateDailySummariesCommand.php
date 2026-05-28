<?php
declare(strict_types=1);

namespace App\Summary\Infrastructure\Console;

use App\Summary\Application\DailySummaryGeneratorInterface;
use App\Summary\Domain\DailySummaryRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generate one day's summary. Loops over multiple days are an orchestration
 * concern — leave them to a shell `for`-loop or a Makefile target. Keeping
 * this command single-date keeps its contract small and makes
 * idempotency a flat statement: "running this command twice on the same
 * date is a no-op (unless --force)".
 */
#[AsCommand(
    name: 'chat:generate-daily-summaries',
    description: 'Generate one Bluesky-snapshot daily synthesis for the given date',
)]
final class GenerateDailySummariesCommand extends Command
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly DailySummaryGeneratorInterface $generator,
        private readonly DailySummaryRepository $repository,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct();
        $this->logger = $logger ?? new NullLogger();
    }

    protected function configure(): void
    {
        $this
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Target day (YYYY-MM-DD)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Re-generate even if a summary file already exists')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print plan only, no LLM call, no disk write');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');

        $dateRaw = $input->getOption('date');
        if (!\is_string($dateRaw) || $dateRaw === '') {
            $io->error('--date is required (YYYY-MM-DD)');

            return Command::INVALID;
        }
        try {
            $date = $this->validateDate($dateRaw);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        if (!$force && $this->repository->exists($date)) {
            $io->writeln("skip {$date}: summary already exists (use --force to re-generate)");

            return Command::SUCCESS;
        }
        if ($dryRun) {
            $io->writeln("dry-run {$date}: would generate");

            return Command::SUCCESS;
        }

        try {
            $summary = $this->generator->generate($date);
        } catch (\Throwable $e) {
            $this->logger->error('chat.summary.failed', [
                'date' => $date,
                'error' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $io->error("failure on {$date}: {$e->getMessage()}");

            return Command::FAILURE;
        }

        if ($summary === null) {
            $this->logger->info('chat.summary.missing-snapshot', ['date' => $date]);
            $io->writeln("missing {$date}: no snapshot to summarise");

            return Command::SUCCESS;
        }

        $this->repository->save($summary);
        // chat.summary.generated is logged by the Generator itself
        // (it has the source data — publication count, etc.).
        $io->success("generated {$date}");

        return Command::SUCCESS;
    }

    private function validateDate(string $value): string
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($d === false || $d->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException("Not a valid YYYY-MM-DD date: {$value}");
        }

        return $value;
    }
}

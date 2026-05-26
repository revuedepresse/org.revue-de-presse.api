<?php
declare(strict_types=1);

namespace App\Chat\Infrastructure\Console;

use App\Chat\Application\Port\PublicationEmbedder;
use App\NewsReview\Domain\Model\HighlightDto;
use App\NewsReview\Domain\Snapshot\Filter\HighlightNormalizer;
use App\NewsReview\Domain\Snapshot\SnapshotReader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'chat:embed-snapshots',
    description: 'Walk Bluesky JSON snapshots and embed each publication into the pgvector store',
)]
final class EmbedSnapshotsCommand extends Command
{
    private const DEFAULT_FROM = '2025-03-04';

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly SnapshotReader $snapshotReader,
        private readonly HighlightNormalizer $normalizer,
        private readonly PublicationEmbedder $embedder,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct();
        $this->logger = $logger ?? new NullLogger();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Inclusive start date (YYYY-MM-DD)', self::DEFAULT_FROM)
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Inclusive end date (YYYY-MM-DD); defaults to today (Europe/Paris)')
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Single-day mode (mutually exclusive with --from/--to)')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Highlights per provider HTTP call', '32')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Walk + parse only, no HTTP, no DB writes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        try {
            $dates = $this->resolveDates($input);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        $io->title('chat:embed-snapshots');
        $io->writeln(sprintf(
            'Dates: %s … %s (%d day%s)',
            $dates[0],
            $dates[count($dates) - 1],
            count($dates),
            count($dates) > 1 ? 's' : '',
        ));
        if ($dryRun) {
            $io->note('Dry-run mode: no HTTP, no DB writes.');
        }

        $totalEmbedded = 0;
        $totalSkipped = 0;
        $totalFailed = 0;
        $progress = new ProgressBar($output, count($dates));
        $progress->start();

        foreach ($dates as $date) {
            try {
                $rows = $this->snapshotReader->read($date);
                if ($rows === []) {
                    $totalSkipped += 1;
                    $progress->advance();
                    continue;
                }

                $highlights = array_map(
                    fn (array $row): HighlightDto => $this->normalizer->toDto($row),
                    array_values(array_filter($rows, 'is_array')),
                );

                foreach (array_chunk($highlights, $batchSize) as $batch) {
                    if (!$dryRun) {
                        $this->embedder->embedBatch($batch);
                    }
                    $totalEmbedded += count($batch);
                }
            } catch (\Throwable $e) {
                $totalFailed += 1;
                $this->logger->error('chat.embed-snapshots.failure', [
                    'date' => $date,
                    'error' => $e::class,
                    'message' => $e->getMessage(),
                ]);
                $io->newLine(2);
                $io->warning("Failure on {$date}: {$e->getMessage()}");
            }

            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf(
            '%d publication(s) embedded across %d snapshot(s) (%d skipped, %d failed)',
            $totalEmbedded,
            count($dates) - $totalSkipped - $totalFailed,
            $totalSkipped,
            $totalFailed,
        ));

        if ($totalFailed > 0 && $totalEmbedded > 0) {
            return 1;
        }
        if ($totalFailed > 0) {
            return 2;
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<string> YYYY-MM-DD, inclusive
     */
    private function resolveDates(InputInterface $input): array
    {
        $single = $input->getOption('date');
        if (is_string($single) && $single !== '') {
            return [$this->validateDate($single)];
        }

        $from = $this->validateDate((string) ($input->getOption('from') ?? self::DEFAULT_FROM));
        $toRaw = $input->getOption('to');
        $to = is_string($toRaw) && $toRaw !== ''
            ? $this->validateDate($toRaw)
            : (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');

        if ($from > $to) {
            throw new \InvalidArgumentException("--from ({$from}) is after --to ({$to})");
        }

        $dates = [];
        $cursor = new \DateTimeImmutable($from);
        $end = new \DateTimeImmutable($to);
        while ($cursor <= $end) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        return $dates;
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

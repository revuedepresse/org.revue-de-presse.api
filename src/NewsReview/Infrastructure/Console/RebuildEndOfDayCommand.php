<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\Console;

use App\NewsReview\Domain\EndOfDay\EndOfDayRebuilder;
use App\NewsReview\Domain\EndOfDay\ParisDay;
use App\NewsReview\Domain\EndOfDay\RankedPost;
use App\NewsReview\Domain\Snapshot\PublisherList;
use App\NewsReview\Domain\Snapshot\UnknownPublisherList;
use App\NewsReview\Infrastructure\EndOfDay\DbalEndOfDayRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rebuilds a day's counts as they stood at 22:00 and at midnight, Paris time,
 * from the times of its posts' reposts and likes, and stores them in
 * status_popularity as measured_by = 'rebuild', next to the fetched ones.
 */
#[AsCommand('highlights:rebuild-end-of-day', 'Rebuild a day\'s end-of-day popularity counts from like and repost times')]
final class RebuildEndOfDayCommand extends Command
{
    public function __construct(
        private readonly EndOfDayRebuilder $rebuilder,
        private readonly DbalEndOfDayRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::REQUIRED, 'Calendar day in Europe/Paris (YYYY-MM-DD)')
            ->addOption('list', null, InputOption::VALUE_REQUIRED, 'Publisher list: fr, coop-medias or fr-coop-medias', 'fr')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the rebuilt top 10 at each cut-off; store nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $day = ParisDay::of((string) $input->getArgument('date'));
            $list = PublisherList::fromQuery((string) $input->getOption('list'));
        } catch (\InvalidArgumentException | UnknownPublisherList $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        $candidates = $this->repository->candidates($day, $list);
        $rebuild = $this->rebuilder->rebuild($day, $candidates);

        foreach (array_keys($day->cutOffs()) as $cutOff) {
            $io->section(sprintf('%s, %s list, at %s (Paris)', $day->date(), $list->value, $cutOff));
            $io->table(
                ['#', 'account', 'reposts', 'likes', 'post'],
                array_map(
                    static fn(RankedPost $post, int $rank): array => [$rank + 1, $post->handle, $post->reposts, $post->likes, $post->uri],
                    $rebuild->topTen($cutOff),
                    array_keys($rebuild->topTen($cutOff)),
                ),
            );
        }

        if ($input->getOption('dry-run')) {
            $io->note(sprintf('Dry run: %d candidate posts, %d rebuilt measurements, nothing stored.', count($candidates), count($rebuild->measurements())));

            return Command::SUCCESS;
        }

        $this->repository->store($rebuild, $candidates);
        $io->success(sprintf('%d rebuilt measurements stored for %d candidate posts.', count($rebuild->measurements()), count($candidates)));

        return Command::SUCCESS;
    }
}

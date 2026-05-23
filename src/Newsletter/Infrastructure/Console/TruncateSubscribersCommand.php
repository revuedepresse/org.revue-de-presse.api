<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Repository\SubscriberRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('newsletter:truncate', 'Delete every newsletter subscriber row (admin-only, destructive)')]
final class TruncateSubscribersCommand extends Command
{
    public function __construct(private readonly SubscriberRepository $repo)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip the confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')
            && !$io->confirm('This will permanently delete EVERY newsletter subscriber row. Continue?', false)) {
            $io->warning('Aborted.');
            return Command::SUCCESS;
        }

        $deleted = $this->repo->truncate();
        $io->success(sprintf('Truncated newsletter_subscribers (%d row(s) cleared).', $deleted));
        return Command::SUCCESS;
    }
}

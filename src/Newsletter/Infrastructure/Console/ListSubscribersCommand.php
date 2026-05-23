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

#[AsCommand('newsletter:list', 'List newsletter subscribers (admin-only)')]
final class ListSubscribersCommand extends Command
{
    public function __construct(private readonly SubscriberRepository $repo)
    { parent::__construct(); }

    protected function configure(): void
    {
        $this->addOption('status', null, InputOption::VALUE_REQUIRED, 'active|pending|unsubscribed', 'active');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];
        foreach ($this->repo->iterateByStatus((string) $input->getOption('status'), 200) as $sub) {
            $rows[] = [
                (string) $sub->id(),
                $sub->email()->unmask(),
                $sub->status()->value,
                $sub->lastSentAt()?->format(\DateTimeInterface::ATOM) ?? '-',
            ];
        }
        $io->table(['id', 'email', 'status', 'last_sent_at'], $rows);
        return Command::SUCCESS;
    }
}

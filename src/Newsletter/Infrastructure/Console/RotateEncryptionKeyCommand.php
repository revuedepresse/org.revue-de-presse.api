<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Repository\SubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('newsletter:rotate-encryption-key', 'Re-encrypt every row using NEWSLETTER_ENCRYPTION_KEY_NEXT')]
final class RotateEncryptionKeyCommand extends Command
{
    public function __construct(
        private readonly SubscriberRepository $repo,
        private readonly EntityManagerInterface $em,
    ) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $env = getenv('NEWSLETTER_ENCRYPTION_KEY_NEXT');
        if ($env === false || $env === '') {
            $io->error('Set NEWSLETTER_ENCRYPTION_KEY_NEXT before running this command.');
            return 4;
        }

        $count = 0;
        // Force a re-encrypt round-trip by reading + dirtying + flushing.
        foreach (['active', 'pending', 'unsubscribed'] as $status) {
            foreach ($this->repo->iterateByStatus($status, 200) as $sub) {
                $sub->markSent($sub->lastSentAt() ?? new \DateTimeImmutable('1970-01-01'));
                $this->repo->save($sub);
                $count++;
            }
        }
        $io->success(sprintf('Re-encrypted %d rows. Now: move NEXT → primary in .env.local and unset NEXT.', $count));
        return Command::SUCCESS;
    }
}

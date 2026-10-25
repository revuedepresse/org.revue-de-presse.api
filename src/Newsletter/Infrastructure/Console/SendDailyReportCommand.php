<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\Service\DailyHighlightsSource;
use App\Newsletter\Domain\Service\DailyReportRenderer;
use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\InvalidEdition;
use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Mailer\MailerInterface;
use Throwable;

#[AsCommand('newsletter:send-daily', 'Send the day\'s newsletter to active subscribers')]
final class SendDailyReportCommand extends Command
{
    public function __construct(
        private readonly SubscriberRepository $repo,
        private readonly DailyHighlightsSource $highlights,
        private readonly DailyReportRenderer $renderer,
        private readonly MailerInterface $mailer,
        private readonly NewsletterConfig $config,
        private readonly LockFactory $newsletterLockFactory,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addOption('date', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD (default: yesterday in configured TZ)');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE);
        $this->addOption('edition', null, InputOption::VALUE_REQUIRED, 'fr|coop-medias|fr-coop-medias (default: every edition)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tz = new \DateTimeZone($this->config->timezone);
        $date = $input->getOption('date')
            ? new \DateTimeImmutable((string) $input->getOption('date'), $tz)
            : $this->clock->now()->setTimezone($tz)->modify('-1 day');
        $dateKey = $date->format('Y-m-d');

        try {
            $editions = $input->getOption('edition') !== null
                ? [Edition::fromForm($input->getOption('edition'))]
                : Edition::cases();
        } catch (InvalidEdition $e) {
            $io->error($e->getMessage());
            return Command::INVALID;
        }

        // Each edition is sent on its own: one with no highlights or failed
        // sends does not hold back the others. The worst outcome is the exit code.
        $exit = Command::SUCCESS;
        foreach ($editions as $edition) {
            $exit = max($exit, $this->sendEdition($edition, $date, $dateKey, (bool) $input->getOption('dry-run'), $io));
        }

        return $exit;
    }

    private function sendEdition(Edition $edition, \DateTimeImmutable $date, string $dateKey, bool $dryRun, SymfonyStyle $io): int
    {
        $label = sprintf('%s %s', $dateKey, $edition->value);
        $lock = $this->newsletterLockFactory->createLock(sprintf('newsletter.daily.%s.%s', $edition->value, $dateKey), 3600);
        if (!$lock->acquire()) {
            $io->warning(sprintf('Lock held for %s — already sent today or another run in progress', $label));
            return 1;
        }

        try {
            try {
                $views = $this->highlights->fetchTop10($date, $edition);
            } catch (\Throwable $e) {
                $this->logger->error('newsletter highlights fetch failed', ['date' => $dateKey, 'edition' => $edition->value, 'error' => $e->getMessage()]);
                $io->error(sprintf('%s: highlights fetch failed: %s', $label, $e->getMessage()));
                return 2;
            }
            if (count($views) === 0) {
                $io->warning('No highlights for ' . $label);
                return 2;
            }

            $sent = 0; $failed = 0; $considered = 0;
            foreach ($this->repo->iterateActive(200, $edition) as $sub) {
                $considered++;
                $email = $this->renderer->render($sub, $views, $date);

                if ($dryRun) {
                    $io->writeln(sprintf('dry-run: would send %s to %s', $edition->value, $sub->email()));
                    continue;
                }

                try {
                    $this->mailer->send($email);
                    $sub->markSent($this->clock->now());
                    $this->repo->save($sub);
                    $sent++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->logger->error('newsletter send failed', ['subscriber_id' => (string) $sub->id(), 'edition' => $edition->value, 'error' => $e->getMessage()]);
                }
            }

            $io->success(sprintf('%s: %d sent, %d failed, %d considered', $label, $sent, $failed, $considered));
            return $failed > 0 ? 3 : 0;
        } finally {
            $lock->release();
        }
    }
}

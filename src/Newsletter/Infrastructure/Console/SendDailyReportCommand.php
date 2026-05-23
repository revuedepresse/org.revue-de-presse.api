<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Repository\SubscriberRepository;
use App\Newsletter\Domain\Service\DailyHighlightsSource;
use App\Newsletter\Domain\Service\DailyReportRenderer;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tz = new \DateTimeZone($this->config->timezone);
        $date = $input->getOption('date')
            ? new \DateTimeImmutable((string) $input->getOption('date'), $tz)
            : $this->clock->now()->setTimezone($tz)->modify('-1 day');
        $dateKey = $date->format('Y-m-d');

        $lock = $this->newsletterLockFactory->createLock('newsletter.daily.' . $dateKey, 3600);
        if (!$lock->acquire()) {
            $io->warning(sprintf('Lock held for %s — already sent today or another run in progress', $dateKey));
            return 1;
        }

        try {
            $views = $this->highlights->fetchTop10($date);
            if (count($views) === 0) {
                $io->warning('No highlights for ' . $dateKey);
                return 2;
            }

            $sent = 0; $failed = 0; $considered = 0;
            foreach ($this->repo->iterateActive(200) as $sub) {
                $considered++;
                $email = $this->renderer->render($sub, $views, $date);

                if ($input->getOption('dry-run')) {
                    $io->writeln(sprintf('dry-run: would send to %s', $sub->email()));
                    continue;
                }

                try {
                    $this->mailer->send($email);
                    $sub->markSent($this->clock->now());
                    $this->repo->save($sub);
                    $sent++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->logger->error('newsletter send failed', ['subscriber_id' => (string) $sub->id(), 'error' => $e->getMessage()]);
                }
            }

            $io->success(sprintf('%s: %d sent, %d failed, %d considered', $dateKey, $sent, $failed, $considered));
            return $failed > 0 ? 3 : 0;
        } finally {
            $lock->release();
        }
    }
}

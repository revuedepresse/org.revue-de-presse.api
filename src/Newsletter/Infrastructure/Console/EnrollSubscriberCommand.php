<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Service\SubscriberEnroller;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand('newsletter:enroll', 'Enrol a recipient (admin-only via SSH)')]
final class EnrollSubscriberCommand extends Command
{
    public function __construct(
        private readonly SubscriberEnroller $enroller,
        private readonly MailerInterface $mailer,
        private readonly NewsletterConfig $config,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = EmailAddress::fromString((string) $input->getArgument('email'));
        $outcome = $this->enroller->enrol($email);

        if ($outcome->confirmToken !== null) {
            $this->sendConfirmation($email, $outcome->confirmToken->value());
        }

        $io->success(sprintf('%s: %s', $email->value(), $outcome->result->value));
        return Command::SUCCESS;
    }

    private function sendConfirmation(EmailAddress $email, string $confirmToken): void
    {
        $msg = (new TemplatedEmail())
            ->from(new Address($this->config->fromEmail, $this->config->fromName))
            ->to(new Address($email->unmask()))
            ->subject('Confirmez votre abonnement à la Revue de presse')
            ->htmlTemplate('newsletter/confirm-email.html.twig')
            ->textTemplate('newsletter/confirm-email.text.twig')
            ->context(['confirm_url' => $this->config->confirmUrl($confirmToken)]);
        $this->mailer->send($msg);
    }
}

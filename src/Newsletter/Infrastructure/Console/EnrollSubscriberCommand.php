<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Console;

use App\Newsletter\Domain\Service\SubscriberEnroller;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Infrastructure\Mail\ConfirmationMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('newsletter:enroll', 'Enrol a recipient (admin-only via SSH)')]
final class EnrollSubscriberCommand extends Command
{
    public function __construct(
        private readonly SubscriberEnroller $enroller,
        private readonly ConfirmationMailer $confirmationMailer,
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
            $this->confirmationMailer->send($email, $outcome->confirmToken->value());
        }

        $io->success(sprintf('%s: %s', $email->value(), $outcome->result->value));
        return Command::SUCCESS;
    }
}

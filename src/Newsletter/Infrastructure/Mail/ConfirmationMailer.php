<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Mail;

use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class ConfirmationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly NewsletterConfig $config,
    ) {}

    public function send(EmailAddress $email, string $confirmToken, Edition $edition = Edition::FrenchMedia): void
    {
        $this->sendForEditions($email, [[$edition, $confirmToken]]);
    }

    /**
     * One mail, one button, for every edition asked for at once.
     *
     * @param non-empty-list<array{Edition, string}> $tokensByEdition
     */
    public function sendForEditions(EmailAddress $email, array $tokensByEdition): void
    {
        $names = array_map(static fn(array $pair): string => $pair[0]->newsletterName(), $tokensByEdition);
        $tokens = array_map(static fn(array $pair): string => $pair[1], $tokensByEdition);

        $msg = (new TemplatedEmail())
            ->from(new Address($this->config->fromEmail, $this->config->fromName))
            ->to(new Address($email->unmask()))
            ->subject('Confirmez votre adresse e-mail — Revue de presse')
            ->htmlTemplate('newsletter/confirm-email.html.twig')
            ->textTemplate('newsletter/confirm-email.text.twig')
            ->context([
                'confirm_url' => $this->config->confirmUrl(...$tokens),
                'confirm_ttl_days' => intdiv($this->config->confirmTtlHours + 23, 24),
                'newsletter_name' => implode(' et ', $names),
            ]);
        $this->mailer->send($msg);
    }
}

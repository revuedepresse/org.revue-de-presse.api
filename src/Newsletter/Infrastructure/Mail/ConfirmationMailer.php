<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Mail;

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

    public function send(EmailAddress $email, string $confirmToken): void
    {
        $msg = (new TemplatedEmail())
            ->from(new Address($this->config->fromEmail, $this->config->fromName))
            ->to(new Address($email->unmask()))
            ->subject('Confirmez votre adresse e-mail — Revue de presse')
            ->htmlTemplate('newsletter/confirm-email.html.twig')
            ->textTemplate('newsletter/confirm-email.text.twig')
            ->context([
                'confirm_url' => $this->config->confirmUrl($confirmToken),
                'confirm_ttl_days' => intdiv($this->config->confirmTtlHours + 23, 24),
            ]);
        $this->mailer->send($msg);
    }
}

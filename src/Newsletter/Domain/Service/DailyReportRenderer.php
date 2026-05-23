<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

use App\Newsletter\Domain\Entity\Subscriber;
use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class DailyReportRenderer
{
    public function __construct(private readonly NewsletterConfig $config)
    {}

    /**
     * @param HighlightView[] $highlights
     */
    public function render(Subscriber $recipient, array $highlights, \DateTimeImmutable $date): Email
    {
        $dateFr = $this->formatDateFr($date);
        $unsubUrl = $this->config->unsubscribeUrl($recipient->unsubToken()->value());

        $email = (new TemplatedEmail())
            ->from(new Address($this->config->fromEmail, $this->config->fromName))
            ->to(new Address($recipient->email()->unmask()))
            ->subject(sprintf('Revue de presse — %s', $dateFr))
            ->htmlTemplate('newsletter/daily-report.html.twig')
            ->textTemplate('newsletter/daily-report.text.twig')
            ->context([
                'date_fr'        => $dateFr,
                'highlights'     => $highlights,
                'unsubscribe_url' => $unsubUrl,
            ]);

        $email->getHeaders()->addTextHeader('List-Unsubscribe', sprintf('<%s>', $unsubUrl));
        $email->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        $email->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
        $email->getHeaders()->addTextHeader('Precedence', 'bulk');
        $email->getHeaders()->addTextHeader('X-Mailer', 'Revue-de-presse');

        return $email;
    }

    private function formatDateFr(\DateTimeImmutable $date): string
    {
        $fmt = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, $this->config->timezone);
        return $fmt->format($date);
    }
}

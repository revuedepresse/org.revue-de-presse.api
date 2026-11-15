<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Mailer;

use App\Newsletter\Domain\Service\DailyReportRenderer;
use App\Newsletter\Infrastructure\Http\Dev\PreviewFixtures;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Email;

/**
 * Gmail and Outlook refuse a `data:` URI in an image: a logo inlined that way
 * reaches the inbox as a broken image. Every image a letter carries has to be
 * attached to the message and referenced by its cid.
 */
final class EmailImagesTest extends KernelTestCase
{
    public function test_confirmation_email_attaches_its_logo(): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('letters@revue-de-presse.org', 'Revue de presse'))
            ->to(new Address('reader@example.org'))
            ->subject('Confirmez votre abonnement à la Revue de presse')
            ->htmlTemplate('newsletter/confirm-email.html.twig')
            ->textTemplate('newsletter/confirm-email.text.twig')
            ->context([
                'confirm_url' => 'https://api.revue-de-presse.org/newsletter/confirm/token',
                'confirm_ttl_days' => 7,
                'newsletter_name' => 'la lettre des médias français',
            ]);

        $html = $this->renderHtml($email);

        self::assertStringNotContainsString('data:image', $html);
        self::assertMatchesRegularExpression('/<img src="cid:[^"]+"/', $html);
        self::assertSame(['brand-logo.png'], $this->inlineImageNames($email));
    }

    public function test_daily_letter_attaches_its_logo_and_metric_icons(): void
    {
        self::bootKernel();
        $renderer = self::getContainer()->get(DailyReportRenderer::class);
        $date = new \DateTimeImmutable('2026-11-14');

        $email = $renderer->render(
            PreviewFixtures::sampleSubscriber(),
            PreviewFixtures::highlights(3, $date),
            $date,
        );

        $html = $this->renderHtml($email);

        self::assertStringNotContainsString('data:image', $html);

        // Three cards, and still one part per image: attaching inside the card
        // would put a copy of each icon in the message for every highlight.
        $attached = $this->inlineImageNames($email);
        sort($attached);
        self::assertSame(['brand-logo.png', 'metric-likes.png', 'metric-reposts.png'], $attached);

        // Each attached part is pointed at from the body, so none travels for
        // nothing. The cid is generated, not the file name, so the parts
        // themselves say what to look for.
        foreach ($email->getAttachments() as $attachment) {
            if ($attachment->getDisposition() !== 'inline') {
                continue;
            }
            self::assertStringContainsString(
                sprintf('cid:%s', $attachment->getContentId()),
                $html,
            );
        }
    }

    /**
     * The page the same partial heads is served rather than sent, and has no
     * message to attach to: there the logo comes from the site.
     */
    public function test_confirmation_page_keeps_a_linked_logo(): void
    {
        self::bootKernel();
        $html = self::getContainer()->get('twig')->render('newsletter/confirmed.html.twig');

        self::assertStringNotContainsString('data:image', $html);
        self::assertStringContainsString('src="/revue-de-presse.png"', $html);
    }

    private function renderHtml(Email $email): string
    {
        self::bootKernel();
        self::getContainer()->get(BodyRendererInterface::class)->render($email);

        return (string) $email->getHtmlBody();
    }

    /** @return string[] */
    private function inlineImageNames(Email $email): array
    {
        $names = [];
        foreach ($email->getAttachments() as $attachment) {
            if ($attachment->getDisposition() === 'inline') {
                $names[] = $attachment->getFilename();
            }
        }

        return $names;
    }
}

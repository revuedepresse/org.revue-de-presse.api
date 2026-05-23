<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Http\Dev;

use App\Newsletter\Domain\Service\DailyReportRenderer;
use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterPreviewController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly DailyReportRenderer $renderer,
        private readonly NewsletterConfig $config,
        private readonly BodyRendererInterface $bodyRenderer,
    ) {
        if (!\in_array($this->kernel->getEnvironment(), ['dev', 'test'], true)) {
            throw new NotFoundHttpException();
        }
    }

    #[Route('/', name: 'newsletter_preview_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('newsletter/_dev/index.html.twig');
    }

    #[Route('/daily-report', name: 'newsletter_preview_daily_report', methods: ['GET'])]
    public function dailyReport(Request $request): Response
    {
        [$date, $count] = $this->parseKnobs($request);
        $email = $this->renderer->render(PreviewFixtures::sampleSubscriber(), PreviewFixtures::highlights($count, $date), $date);
        $this->bodyRenderer->render($email);
        return new Response((string) $email->getHtmlBody());
    }

    #[Route('/daily-report.txt', name: 'newsletter_preview_daily_report_text', methods: ['GET'])]
    public function dailyReportText(Request $request): Response
    {
        [$date, $count] = $this->parseKnobs($request);
        $email = $this->renderer->render(PreviewFixtures::sampleSubscriber(), PreviewFixtures::highlights($count, $date), $date);
        $this->bodyRenderer->render($email);
        return new Response((string) $email->getTextBody(), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    #[Route('/confirmed', name: 'newsletter_preview_confirmed', methods: ['GET'])]
    public function confirmed(): Response { return $this->render('newsletter/confirmed.html.twig'); }

    #[Route('/confirm-failed', name: 'newsletter_preview_confirm_failed', methods: ['GET'])]
    public function confirmFailed(): Response { return $this->render('newsletter/confirm-failed.html.twig'); }

    #[Route('/unsubscribe-confirm', name: 'newsletter_preview_unsubscribe_confirm', methods: ['GET'])]
    public function unsubscribeConfirm(): Response { return $this->render('newsletter/unsubscribe-confirm.html.twig'); }

    #[Route('/unsubscribed', name: 'newsletter_preview_unsubscribed', methods: ['GET'])]
    public function unsubscribed(): Response { return $this->render('newsletter/unsubscribed.html.twig'); }

    /** @return array{\DateTimeImmutable, int} */
    private function parseKnobs(Request $request): array
    {
        $dateRaw = (string) $request->query->get('date', (new \DateTimeImmutable('-1 day'))->format('Y-m-d'));
        $date = new \DateTimeImmutable($dateRaw, new \DateTimeZone($this->config->timezone));
        $count = (int) $request->query->get('count', 10);
        return [$date, $count];
    }
}

<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Service\SubscriberEnroller;
use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\EmailAddress;
use App\Newsletter\Domain\ValueObject\InvalidEdition;
use App\Newsletter\Domain\ValueObject\InvalidEmailAddress;
use App\Newsletter\Infrastructure\Config\NewsletterConfig;
use App\Newsletter\Infrastructure\Mail\ConfirmationMailer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public sign-up, posted to by the plain HTML form on revue-de-presse.org.
 *
 * The response never reveals whether an address is already subscribed: valid,
 * already-active, throttled and honeypot submissions all render the same
 * "check your inbox" page. Double opt-in is preserved — nothing is sent to an
 * address until its owner clicks the confirmation link.
 */
final class SubscribeController extends AbstractController
{
    public const ENROLLED_BY = 'public-form';

    public function __construct(
        private readonly string $signupUrl,
        private readonly NewsletterConfig $config,
    ) {}

    #[Route('/subscribe', name: 'newsletter_subscribe_form', methods: ['GET'])]
    public function form(): Response
    {
        return new RedirectResponse($this->signupUrl);
    }

    #[Route('/subscribe', name: 'newsletter_subscribe_post', methods: ['POST'])]
    public function subscribe(
        Request $request,
        SubscriberEnroller $enroller,
        ConfirmationMailer $confirmationMailer,
        RateLimiterFactory $newsletterSubscribeEmailLimiter,
        LoggerInterface $newsletterLogger,
    ): Response {
        // Hidden field humans never fill in.
        if (trim((string) $request->request->get('website', '')) !== '') {
            return $this->renderResponse('newsletter/subscribe-pending.html.twig');
        }

        try {
            $email = EmailAddress::fromString((string) $request->request->get('email', ''));
            // fr, coop-medias or both; without one, the French media letter.
            $editions = Edition::listFromForm($request->request->all()['edition'] ?? null);
        } catch (InvalidEmailAddress|InvalidEdition) {
            return $this->renderResponse('newsletter/subscribe-invalid.html.twig', 422);
        }

        // Caps confirmation mails per address so the form cannot be used to
        // flood someone else's inbox from many IPs.
        if (!$newsletterSubscribeEmailLimiter->create($email->hash())->consume()->isAccepted()) {
            return $this->renderResponse('newsletter/subscribe-pending.html.twig');
        }

        $tokensByEdition = [];
        foreach ($editions as $edition) {
            $outcome = $enroller->enrol($email, self::ENROLLED_BY, $edition);
            if ($outcome->confirmToken !== null) {
                $tokensByEdition[] = [$edition, $outcome->confirmToken->value()];
            }
        }
        if ($tokensByEdition !== []) {
            try {
                $confirmationMailer->sendForEditions($email, $tokensByEdition);
            } catch (TransportExceptionInterface $e) {
                $newsletterLogger->error('newsletter confirmation send failed', ['email' => (string) $email, 'error' => $e->getMessage()]);
                return $this->renderResponse('newsletter/subscribe-failed.html.twig', 503);
            }
        }

        return $this->renderResponse('newsletter/subscribe-pending.html.twig');
    }

    private function renderResponse(string $template, int $status = 200): Response
    {
        $response = $this->render($template, [
            'confirm_ttl_days' => intdiv($this->config->confirmTtlHours + 23, 24),
        ]);
        $response->setStatusCode($status);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}

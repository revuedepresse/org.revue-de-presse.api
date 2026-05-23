<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Service\SubscriberUnsubscriber;
use App\Newsletter\Domain\ValueObject\InvalidOpaqueToken;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class UnsubscribeController extends AbstractController
{
    #[Route('/unsubscribe/{token}', name: 'newsletter_unsubscribe_form', methods: ['GET'])]
    public function form(string $token): Response
    {
        return $this->renderResponse('newsletter/unsubscribe-confirm.html.twig');
    }

    #[Route('/unsubscribe/{token}', name: 'newsletter_unsubscribe_post', methods: ['POST'])]
    public function unsubscribe(
        string $token,
        Request $request,
        SubscriberUnsubscriber $service,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        $body = $request->getContent();
        $isOneClick = ($request->headers->get('Content-Type') === 'application/x-www-form-urlencoded'
                       && trim($body) === 'List-Unsubscribe=One-Click')
                   || $request->request->get('List-Unsubscribe') === 'One-Click';

        if (!$isOneClick) {
            $submitted = (string) $request->request->get('_csrf_token', '');
            if (!$csrf->isTokenValid(new CsrfToken('newsletter-unsubscribe', $submitted))) {
                return $this->renderResponse('newsletter/confirm-failed.html.twig', 200);
            }
        }

        try {
            $opaque = OpaqueToken::fromString($token);
        } catch (InvalidOpaqueToken) {
            return $this->renderResponse('newsletter/confirm-failed.html.twig');
        }

        $service->unsubscribe($opaque);
        return $this->renderResponse('newsletter/unsubscribed.html.twig');
    }

    private function renderResponse(string $template, int $status = 200): Response
    {
        $response = $this->render($template);
        $response->setStatusCode($status);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}

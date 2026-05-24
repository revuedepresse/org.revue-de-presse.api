<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Service\ConfirmationResult;
use App\Newsletter\Domain\Service\SubscriberConfirmer;
use App\Newsletter\Domain\ValueObject\InvalidOpaqueToken;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmController extends AbstractController
{
    #[Route('/confirm/{token}', name: 'newsletter_confirm', methods: ['GET'])]
    public function __invoke(string $token, SubscriberConfirmer $confirmer): Response
    {
        try {
            $opaque = OpaqueToken::fromString($token);
        } catch (InvalidOpaqueToken) {
            return $this->renderResponse('newsletter/confirm-failed.html.twig');
        }

        $result = $confirmer->confirm($opaque);
        return match ($result) {
            ConfirmationResult::Confirmed, ConfirmationResult::AlreadyActive => $this->renderResponse('newsletter/confirmed.html.twig'),
            ConfirmationResult::InvalidOrExpired => $this->renderResponse('newsletter/confirm-failed.html.twig'),
        };
    }

    private function renderResponse(string $template): Response
    {
        $response = $this->render($template);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}

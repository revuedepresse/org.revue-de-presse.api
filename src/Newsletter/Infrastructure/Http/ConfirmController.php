<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Http;

use App\Newsletter\Domain\Service\ConfirmationResult;
use App\Newsletter\Domain\Service\SubscriberConfirmer;
use App\Newsletter\Domain\ValueObject\Edition;
use App\Newsletter\Domain\ValueObject\InvalidOpaqueToken;
use App\Newsletter\Domain\ValueObject\OpaqueToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmController extends AbstractController
{
    #[Route('/confirm/{token}', name: 'newsletter_confirm', methods: ['GET'])]
    public function __invoke(string $token, Request $request, SubscriberConfirmer $confirmer): Response
    {
        // ?and= carries the other editions asked for in the same sign-up.
        $others = array_filter(explode(',', (string) $request->query->get('and', '')));

        $confirmed = false;
        foreach ([$token, ...array_slice($others, 0, count(Edition::cases()) - 1)] as $value) {
            try {
                $result = $confirmer->confirm(OpaqueToken::fromString($value));
            } catch (InvalidOpaqueToken) {
                continue;
            }
            $confirmed = $confirmed || $result !== ConfirmationResult::InvalidOrExpired;
        }

        return $this->renderResponse($confirmed ? 'newsletter/confirmed.html.twig' : 'newsletter/confirm-failed.html.twig');
    }

    private function renderResponse(string $template): Response
    {
        $response = $this->render($template);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}

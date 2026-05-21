<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Controller;

use App\TikTok\Infrastructure\Http\TikTokWebhookVerificationException;
use App\TikTok\Infrastructure\Http\TikTokWebhookVerifier;
use App\TikTok\Infrastructure\Http\UnconfiguredTikTokWebhookException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public callback for TikTok event-subscription deliveries. Verifies the
 * HMAC signature on the raw body and acknowledges receipt with 200
 * `{"ok": true}`; downstream handlers are wired separately.
 *
 * Mirrors the NestJS adapter's `TikTokWebhookController`. The route is
 * declared `PUBLIC_ACCESS` in `config/packages/security.yaml` since TikTok
 * authenticates itself via the signature, not a Bearer.
 */
final class TikTokWebhookController
{
    public function __construct(
        private readonly TikTokWebhookVerifier $verifier,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[Route('/api/tiktok/webhook/callback', name: 'app_tiktok_webhook_callback', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $signature = $request->headers->get('tiktok-signature');
        $rawBody   = $request->getContent();

        try {
            $envelope = $this->verifier->verifyAndParse($signature, $rawBody);
        } catch (UnconfiguredTikTokWebhookException $e) {
            return $this->problemJson(
                Response::HTTP_SERVICE_UNAVAILABLE,
                'TikTok webhook secret not configured',
                $e->getMessage(),
            );
        } catch (TikTokWebhookVerificationException $e) {
            return $this->problemJson(
                Response::HTTP_UNAUTHORIZED,
                'TikTok webhook verification failed',
                $e->reason,
            );
        }

        $this->logger->info(sprintf(
            'tiktok webhook event=%s user=%s',
            $envelope->event,
            $envelope->user_openid ?? '-',
        ));

        return new JsonResponse(['ok' => true], Response::HTTP_OK, ['Cache-Control' => 'no-store']);
    }

    private function problemJson(int $status, string $title, string $detail): JsonResponse
    {
        return new JsonResponse(
            [
                'type'   => 'about:blank',
                'title'  => $title,
                'status' => $status,
                'detail' => $detail,
            ],
            $status,
            [
                'Content-Type'  => 'application/problem+json',
                'Cache-Control' => 'no-store',
            ],
        );
    }
}

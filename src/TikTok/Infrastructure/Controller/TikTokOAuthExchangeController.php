<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Controller;

use App\TikTok\Infrastructure\Http\TikTokExchangeException;
use App\TikTok\Infrastructure\Http\TikTokOAuthClient;
use App\TikTok\Infrastructure\Http\UnconfiguredTikTokClientException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Server-side exchange of a TikTok `authorization_code` against TikTok's
 * token endpoint. Gated behind the existing access_token firewall — the
 * NestJS sibling uses the same `^/api → ROLE_USER` rule.
 *
 * Mirrors the NestJS adapter's `TikTokOAuthController::exchange`. The
 * upstream call is delegated to {@see TikTokOAuthClient}; everything in
 * here is just JSON parsing + RFC 7807 mapping.
 */
final class TikTokOAuthExchangeController
{
    public function __construct(
        private readonly TikTokOAuthClient $client,
    ) {
    }

    #[Route('/api/tiktok/oauth/exchange', name: 'app_tiktok_oauth_exchange', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $payload = $this->decodeJsonBody($request);
        if ($payload === null) {
            return $this->problemJson(
                Response::HTTP_BAD_REQUEST,
                'Invalid request body',
                'Request body must be a JSON object.',
            );
        }

        $code         = $this->nonEmptyString($payload, 'code');
        $codeVerifier = $this->nonEmptyString($payload, 'code_verifier');
        $redirectUri  = $this->nonEmptyString($payload, 'redirect_uri');

        if ($code === null || $codeVerifier === null || $redirectUri === null) {
            return $this->problemJson(
                Response::HTTP_BAD_REQUEST,
                'Invalid request body',
                'Fields `code`, `code_verifier`, `redirect_uri` are all required and must be non-empty strings.',
            );
        }

        try {
            $tokens = $this->client->exchangeCode($code, $codeVerifier, $redirectUri);
        } catch (UnconfiguredTikTokClientException $e) {
            return $this->problemJson(
                Response::HTTP_SERVICE_UNAVAILABLE,
                'TikTok credentials not configured',
                $e->getMessage(),
            );
        } catch (TikTokExchangeException $e) {
            return $this->problemJson(
                Response::HTTP_BAD_REQUEST,
                'TikTok exchange failed',
                $e->getDetail(),
            );
        }

        return new JsonResponse(
            $tokens->toArray(),
            Response::HTTP_OK,
            ['Cache-Control' => 'no-store'],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonBody(Request $request): ?array
    {
        $raw = $request->getContent();
        if ($raw === '') {
            return null;
        }
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function nonEmptyString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return (is_string($value) && $value !== '') ? $value : null;
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

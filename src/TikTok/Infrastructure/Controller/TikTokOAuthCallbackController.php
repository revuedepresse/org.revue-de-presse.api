<?php
declare(strict_types=1);

namespace App\TikTok\Infrastructure\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public landing page for TikTok's OAuth `redirect_uri`. Renders the
 * incoming `code` + `state` (and the full callback URL) so the maintainer
 * can paste them into the bootstrap CLI that performs the actual exchange.
 *
 * Mirrors the NestJS adapter's `TikTokOAuthController::callback`. All
 * rendered values are HTML-escaped, the page sets `Cache-Control: no-store`,
 * and missing/invalid parameters return RFC 7807 problem+json.
 */
final class TikTokOAuthCallbackController
{
    #[Route('/api/tiktok/oauth/callback', name: 'app_tiktok_oauth_callback', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $code             = $this->stringOrNull($request->query->get('code'));
        $state            = $this->stringOrNull($request->query->get('state'));
        $error            = $this->stringOrNull($request->query->get('error'));
        $errorDescription = $this->stringOrNull($request->query->get('error_description'));

        if ($error !== null) {
            $detail = $errorDescription !== null
                ? sprintf('%s: %s', $error, $errorDescription)
                : $error;

            return $this->problemJson('TikTok OAuth error', $detail);
        }

        if ($code === null || $state === null) {
            return $this->problemJson(
                'Missing OAuth parameters',
                'Both `code` and `state` query parameters are required on the TikTok OAuth callback.',
            );
        }

        $html = $this->renderCallbackHtml($code, $state, $request->getUri());

        return new Response($html, Response::HTTP_OK, [
            'Content-Type'  => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function problemJson(string $title, string $detail): JsonResponse
    {
        return new JsonResponse(
            [
                'type'   => 'about:blank',
                'title'  => $title,
                'status' => Response::HTTP_BAD_REQUEST,
                'detail' => $detail,
            ],
            Response::HTTP_BAD_REQUEST,
            [
                'Content-Type'  => 'application/problem+json',
                'Cache-Control' => 'no-store',
            ],
        );
    }

    private function renderCallbackHtml(string $code, string $state, string $fullUrl): string
    {
        $codeEsc  = htmlspecialchars($code, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stateEsc = htmlspecialchars($state, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $urlEsc   = htmlspecialchars($fullUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>TikTok OAuth callback</title>
  <meta name="robots" content="noindex" />
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
    h1 { font-size: 1.25rem; }
    pre, textarea { background: #f4f4f4; padding: 0.75rem; border-radius: 4px; font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 0.875rem; }
    textarea { width: 100%; box-sizing: border-box; min-height: 5rem; }
    .label { font-weight: 600; margin-top: 1rem; }
  </style>
</head>
<body>
  <h1>TikTok OAuth callback received</h1>
  <p>Paste this URL back into the bootstrap CLI:</p>
  <textarea readonly aria-label="Callback URL">{$urlEsc}</textarea>
  <p class="label">code</p>
  <pre>{$codeEsc}</pre>
  <p class="label">state</p>
  <pre>{$stateEsc}</pre>
</body>
</html>
HTML;
    }
}

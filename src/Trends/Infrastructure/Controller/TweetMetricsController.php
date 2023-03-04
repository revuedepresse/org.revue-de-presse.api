<?php
declare(strict_types=1);

namespace App\Trends\Infrastructure\Controller;

use App\Twitter\Infrastructure\Publication\Repository\HighlightRepository;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TweetMetricsController
{
    use CorsHeadersAwareTrait;

    public HighlightRepository $highlightRepository;

    public LoggerInterface $logger;

    public function tweetMetrics(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $tweetId = $request->attributes->get('tweetId');
        $metrics = $this->highlightRepository->tweetMetrics($tweetId);

        return $this->makeOkResponse($metrics);
    }

    private function makeOkResponse($data): JsonResponse
    {
        return new JsonResponse(
            $data,
            200,
            $this->getAccessControlOriginHeaders(
                $this->environment,
                $this->allowedOrigin
            )
        );
    }
}

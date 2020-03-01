<?php
declare(strict_types=1);

namespace App\Infrastructure\Healthcheck\Controller;

use App\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class HealthcheckController
{
    use CorsHeadersAwareTrait;

    public function areServicesHealthy(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        return new JsonResponse(
            [],
            200,
            $this->getAccessControlOriginHeaders($this->environment, $this->allowedOrigin)
        );

    }
}
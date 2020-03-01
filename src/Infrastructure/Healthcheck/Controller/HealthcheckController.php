<?php
declare(strict_types=1);

namespace App\Infrastructure\Healthcheck\Controller;

use App\Security\Cors\CorsHeadersAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class HealthcheckController
{
    use CorsHeadersAwareTrait;

    private string $environment;

    private string $allowedOrigin;

    public function __construct($allowedOrigin, $environment)
    {
        $this->allowedOrigin = $allowedOrigin;
        $this->environment = $environment;
    }

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
<?php
declare(strict_types=1);

namespace App\Healthcheck\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthcheckController
{
    #[Route('/api/healthcheck', name: 'app_healthcheck', methods: ['GET', 'OPTIONS'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([], 200, ['Cache-Control' => 'no-store']);
    }
}

<?php

namespace App\Security\Cors;

use Symfony\Component\HttpFoundation\JsonResponse;

trait CorsHeadersAwareTrait
{
    /**
     * @param string $environment
     * @param string $allowedOrigin
     * @return JsonResponse
     */
    protected function getCorsOptionsResponse(
        string $environment,
        string $allowedOrigin
    ) {
        $allowedHeaders = implode(
            ', ',
            [
                'Keep-Alive',
                'User-Agent',
                'X-Requested-With',
                'If-Modified-Since',
                'Cache-Control',
                'Content-Type',
                'x-auth-token',
                'x-auth-admin-token',
                'x-total-pages',
                'x-page-index',
                'x-decompressed-content-length'
            ]
        );

        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => $allowedHeaders,
            'Access-Control-Expose-Headers' => $allowedHeaders,
        ];
        if ($environment === 'prod') {
            $headers = [
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Headers' => $allowedHeaders,
                // @see https://stackoverflow.com/a/37931084/282073
                'Access-Control-Expose-Headers' => $allowedHeaders,
            ];
        }

        return new JsonResponse(
            [],
            200,
            $headers
        );
    }
}

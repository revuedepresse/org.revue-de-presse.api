<?php

namespace App\Aggregate\Controller;

use App\Cache\RedisCache;
use App\Security\Cors\CorsHeadersAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;

class AggregateController
{
    use CorsHeadersAwareTrait;

    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

    /**
     * @var string
     */
    public $environment;

    /**
     * @var string
     */
    public $allowedOrigin;

    /**
     * @var RedisCache
     */
    public $redisCache;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkRemoveAggregates(Request $request)
    {
        return $this->applyToAggregateCollection(
            $request,
            function ($aggregateIds) {
                $this->aggregateRepository->bulkRemoveAggregates($aggregateIds);
                $client = $this->redisCache->getClient();
                $client->set('aggregates.recent_delete', json_encode($aggregateIds));
            }
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function collectAggregatesStatuses(Request $request)
    {
        return $this->applyToAggregateCollection(
            $request,
            function ($aggregateIds) {
                $this->aggregateRepository->publishStatusesForAggregates($aggregateIds);
                $client = $this->redisCache->getClient();
                $client->set('aggregates.recent_statuses_collect', json_encode($aggregateIds));
            }
        );
    }

    /**
     * @param Request  $request
     * @param callable $apply
     * @return JsonResponse
     */
    private function applyToAggregateCollection(Request $request, callable $apply): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $corsHeaders = $this->getAccessControlOriginHeaders(
            $this->environment,
            $this->allowedOrigin
        );

        $decodedContent = json_decode($request->getContent(), $decodeAsAssociativeArray = true);
        if (json_last_error() !== JSON_ERROR_NONE ||
            !is_array($decodedContent) ||
            !array_key_exists('params', $decodedContent) ||
            !array_key_exists('aggregateIds', $decodedContent['params'])
        ) {
            return new JsonResponse(
                'Could not process aggregates without valid identifiers',
                422,
                $corsHeaders
            );
        }

        if (count($decodedContent['params']['aggregateIds']) > 0) {
            $apply($decodedContent['params']['aggregateIds']);
        }

        return new JsonResponse(
            null,
            204,
            $corsHeaders
        );
    }
}

<?php
declare(strict_types=1);

namespace App\PublishersList\Controller;

use App\Twitter\Infrastructure\Cache\RedisCache;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Twitter\Infrastructure\Api\Repository\PublishersListRepository;
use function array_key_exists;
use function is_array;
use const JSON_THROW_ON_ERROR;

class PublishersListController
{
    use CorsHeadersAwareTrait;
    use PublishersListRepositoryTrait;

    /**
     * @var PublishersListRepository
     */

    /**
     * @var RedisCache
     */
    public RedisCache $redisCache;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkRemoveAggregates(Request $request)
    {
        return $this->applyToAggregateCollection(
            $request,
            function ($aggregateIds) {
                $this->publishersListRepository->bulkRemoveAggregates($aggregateIds);
                $client = $this->redisCache->getClient();
                $client->set('aggregates.recent_delete', json_encode($aggregateIds));
            }
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function collectAggregatesStatuses(Request $request): JsonResponse
    {
        return $this->applyToAggregateCollection(
            $request,
            function ($aggregateIds) {
                $this->publishersListRepository->publishStatusesForAggregates($aggregateIds);
                $client = $this->redisCache->getClient();
                $client->set(
                    'aggregates.recent_statuses_collect',
                    \json_encode($aggregateIds, JSON_THROW_ON_ERROR)
                );
            }
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetTotalStatusesForAggregates(Request $request)
    {
        return $this->applyToAggregateCollection(
            $request,
            function ($aggregateIds) {
                $this->publishersListRepository->resetTotalStatusesForAggregates($aggregateIds);
                $client = $this->redisCache->getClient();
                $client->set('aggregates.total_statuses_reset', json_encode($aggregateIds));
            }
        );
    }

    public function getPublishersLists(Request $request): JsonResponse
    {
        $memberOrJsonResponse = $this->authenticateMember($request);
        if ($memberOrJsonResponse instanceof JsonResponse) {
            return $memberOrJsonResponse;
        }

        $publishersLists = $this->publishersListRepository
            ->getAllPublishersLists($request);

        return new JsonResponse(
            $publishersLists,
            200,
            $this->getAccessControlOriginHeaders(
                $this->environment,
                $this->allowedOrigin
            )
        );
    }

    /**
     * @param Request  $request
     * @param callable $apply
     * @return JsonResponse
     */
    private function applyToAggregateCollection(
        Request $request,
        callable $apply
    ): JsonResponse {
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

        $decodedContent = \json_decode(
            $request->getContent(),
            $decodeAsAssociativeArray = true,
            512,
            JSON_THROW_ON_ERROR
        );
        if (!is_array($decodedContent) ||
            !array_key_exists('params', $decodedContent) ||
            !array_key_exists('aggregateIds', $decodedContent['params']) ||
            json_last_error() !== JSON_ERROR_NONE
        ) {
            return new JsonResponse(
                'Could not process aggregates without valid identifiers',
                422,
                $corsHeaders
            );
        }

        if (\count($decodedContent['params']['aggregateIds']) > 0) {
            $apply($decodedContent['params']['aggregateIds']);
        }

        return new JsonResponse(
            null,
            204,
            $corsHeaders
        );
    }
}

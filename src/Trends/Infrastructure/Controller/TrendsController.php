<?php
declare(strict_types=1);

namespace App\Trends\Infrastructure\Controller;

use App\Trends\Domain\Repository\PopularPublicationRepositoryInterface;
use App\Cache\RedisCache;
use App\Twitter\Infrastructure\Http\SearchParams;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use JsonException;
use Predis\Connection\ConnectionException;
use Psr\Log\LoggerInterface;
use RedisException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TrendsController
{
    use CorsHeadersAwareTrait;

    public LoggerInterface $logger;

    public RedisCache $redisCache;

    public PopularPublicationRepositoryInterface $popularPublicationRepository;

    public function callback(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $structuredResponse = array_map(
            fn($val) => $request->get($val, ''),
            [
                'expires_in',
                'access_token',
                'scope',
                'refresh_token',
                'refresh_expires_in',
                'token_type open_id'
            ]
        );

        $this->logger->error(
            print_r($structuredResponse, true)
        );

        return new JsonResponse(<<<DATA
    That's all folks! 🤙
DATA
);
    }

    /**
     * @throws JsonException
     * @throws RedisException
     * @throws Exception
     */
    public function getHighlights(Request $request): JsonResponse
    {
        $bypassCache = $this->environment !== 'prod'
            && $request->headers->has('x-benchmark');

        // Request-scoped state captured by the finder closure; threaded out by
        // reference instead of stored on the controller. Worker-mode-safe: no
        // singleton property to leak between requests.
        $cacheState = $bypassCache ? 'bypass' : 'unknown';

        $response = $this->getCollection(
            $request,
            counter: fn(SearchParams $searchParams) => $this->getTotalPages($searchParams, $bypassCache),
            finder: function (SearchParams $searchParams) use ($bypassCache, &$cacheState) {
                return $this->getHighlightsFromSearchParams($searchParams, $bypassCache, $cacheState);
            },
            params: [
                'aggregate'          => 'string',
                'distinctSources'    => 'bool',
                'endDate'            => 'datetime',
                'excludeMedia'       => 'bool',
                'includeRetweets'    => 'bool',
                'routeName'          => 'string',
                'selectedAggregates' => 'array',
                'startDate'          => 'datetime',
                'term'               => 'string',
            ]
        );

        $response->headers->set('x-cache', $cacheState);

        return $response;
    }

    private function getTotalPages(SearchParams $searchParams, bool $bypassCache = false): JsonResponse|int
    {
        $headers = $this->getAccessControlOriginHeaders($this->environment, $this->allowedOrigin);
        $unauthorizedJsonResponse = new JsonResponse(
            'Unauthorized request',
            403,
            $headers
        );

        if ($this->invalidHighlightsSearchParams($searchParams)) {
            return $unauthorizedJsonResponse;
        }

        $totalPages = 1;

        if ($bypassCache) {
            return $totalPages;
        }

        $key = $this->getCacheKey('highlights.total_pages', $searchParams);

        $client = $this->redisCache->getClient();

        try {
            $client->setex($key, 3600, $totalPages);
        } catch (ConnectionException $exception) {
            $this->logger->error($exception->getMessage());

            return $totalPages;
        }

        return $totalPages;
    }

    /**
     * @throws JsonException
     * @throws RedisException
     */
    private function getHighlightsFromSearchParams(
        SearchParams $searchParams,
        bool $bypassCache,
        string &$cacheState,
    ): array {
        if ($this->invalidHighlightsSearchParams($searchParams)) {
            return [];
        }

        if ($bypassCache) {
            $cacheState = 'bypass';
            return $this->popularPublicationRepository->findBy($searchParams);
        }

        $key = $this->getCacheKey('highlights.items', $searchParams);
        $client = $this->redisCache->getClient();

        try {
            $cachedHighlights = $client->get($key);

            if ($cachedHighlights) {
                $cacheState = 'hit';
                return json_decode($cachedHighlights, associative: true, flags: JSON_THROW_ON_ERROR);
            }

            $cacheState = 'miss';
            $highlights = $this->popularPublicationRepository->findBy($searchParams);
            $client->setex($key, 3600, json_encode($highlights, JSON_THROW_ON_ERROR));

            return $highlights;
        } catch (ConnectionException $exception) {
            $this->logger->error($exception->getMessage());

            $cacheState = 'error';
            return $this->popularPublicationRepository->findBy($searchParams);
        }
    }

    public function getCacheKey(string $prefix, SearchParams $searchParams): string
    {
        $includeMedia = 'includeMedia=' . intval($searchParams->includeMedia());
        $includedRetweets = 'includeRetweets=' . $searchParams->getParams()['includeRetweets'];
        $curatingFromDistinctSources = 'fromDistinctSources=' . $searchParams->curatingHighlightsFromDistinctSources();

        $sortedSelectedAggregates = [];
        if ($searchParams->hasParam('selectedAggregates')) {
            $sortedSelectedAggregates = $searchParams->getParams()['selectedAggregates'];
            sort($sortedSelectedAggregates);
        }

        $term = '';
        if ($searchParams->hasParam('term')) {
            $term = $searchParams->getParams()['term'];
        }

        return implode(
            ';'
            , [
                $prefix,
                $searchParams->getParams()['startDate']->format('Y-m-d H'),
                $searchParams->getParams()['endDate']->format('Y-m-d H'),
                implode(',', $sortedSelectedAggregates),
                $includedRetweets,
                $includeMedia,
                $curatingFromDistinctSources,
                $term
            ]
        );
    }

    private function invalidHighlightsSearchParams(SearchParams $searchParams): bool
    {
        return !\array_key_exists('startDate', $searchParams->getParams()) ||
            (!($searchParams->getParams()['startDate'] instanceof \DateTime)) ||
            !\array_key_exists('endDate', $searchParams->getParams()) ||
            (!($searchParams->getParams()['endDate'] instanceof \DateTime)) ||
            !\array_key_exists('includeRetweets', $searchParams->getParams());
    }

    /**
     * @throws Exception
     */
    private function getCollection(
        Request $request,
        callable $counter,
        callable $finder,
        array $params = []
    ): JsonResponse {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        try {
            $searchParams = SearchParams::fromRequest($request, $params);
        } catch (Exception $e) {
            $this->logger->notice($e->getMessage());

            return new JsonResponse(status: 404);
        }

        try {
            $totalPagesOrResponse = $counter($searchParams);
        } catch (NonUniqueResultException $exception) {
            $this->logger->critical($exception->getMessage());

            return new JsonResponse(
                'Sorry, an unexpected error has occurred',
                501,
                $this->getAccessControlOriginHeaders(
                    $this->environment,
                    $this->allowedOrigin
                )
            );
        }


        if ($totalPagesOrResponse instanceof JsonResponse) {
            return $totalPagesOrResponse;
        }

        $totalPagesHeader = ['x-total-pages' => $totalPagesOrResponse];
        $pageIndexHeader = ['x-page-index' => $searchParams->getPageIndex()];

        $items = $finder($searchParams);

        $response = $this->makeOkResponse($items);
        $response->headers->add($totalPagesHeader);
        $response->headers->add($pageIndexHeader);

        return $response;
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

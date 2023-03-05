<?php
declare(strict_types=1);

namespace App\Trends\Infrastructure\Controller;

use App\Trends\Infrastructure\Controller\Exception\InvalidRequestException;
use App\Trends\Domain\Repository\PopularPublicationRepositoryInterface;
use App\Twitter\Infrastructure\Cache\RedisCache;
use App\Twitter\Infrastructure\Http\AccessToken\Repository\TokenRepository;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Entity\TokenInterface;
use App\Twitter\Infrastructure\Http\SearchParams;
use App\Twitter\Infrastructure\Publication\Repository\HighlightRepository;
use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class TrendsController
{
    use CorsHeadersAwareTrait;

    public TokenRepository $tokenRepository;

    public MemberRepository $memberRepository;

    public HighlightRepository $highlightRepository;

    public LoggerInterface $logger;

    public RedisCache $redisCache;

    public RouterInterface $router;

    public PopularPublicationRepositoryInterface $popularPublicationRepository;

    /**
     * @throws \JsonException
     * @throws \RedisException
     */
    public function getHighlights(Request $request)
    {
        return $this->getCollection(
            $request,
            counter: function (SearchParams $searchParams) {
                return $this->getTotalPages($searchParams);
            },
            finder: function (SearchParams $searchParams) {
                return $this->getHighlightsFromSearchParams($searchParams);
            },
            params: [
                'aggregate'          => 'string',
                'endDate'            => 'datetime',
                'includeRetweets'    => 'bool',
                'excludeMedia'       => 'bool',
                'routeName'          => 'string',
                'selectedAggregates' => 'array',
                'startDate'          => 'datetime',
                'term'               => 'string',
            ]
        );
    }

    private function getTotalPages(SearchParams $searchParams): JsonResponse|int
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

        $key = $this->getCacheKey('highlights.total_pages', $searchParams);

        $client = $this->redisCache->getClient();
        $totalPages = 1;
        $client->setex($key, 3600, $totalPages);

        return $totalPages;
    }

    /**
     * @throws \JsonException
     * @throws \RedisException
     */
    private function getHighlightsFromSearchParams(SearchParams $searchParams): array {
        if ($this->invalidHighlightsSearchParams($searchParams)) {
            return [];
        }

        $key = $this->getCacheKey('highlights.items', $searchParams);
        $client = $this->redisCache->getClient();
        $cachedHighlights = $client->get($key);

        if (!$cachedHighlights) {
            $highlights = $this->popularPublicationRepository->findBy($searchParams);
            $client->setex($key, 3600, json_encode($highlights, JSON_THROW_ON_ERROR));

            return $highlights;
        }

        return json_decode($cachedHighlights, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    public function getCacheKey(string $prefix, SearchParams $searchParams): string
    {
        $includeMedia = 'includeMedia=' . intval($searchParams->includeMedia());
        $includedRetweets = 'includeRetweets=' . $searchParams->getParams()['includeRetweets'];

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
     * @throws \Exception
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
        } catch (\Exception $e) {
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

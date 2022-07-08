<?php
declare(strict_types=1);

namespace App\PublishersList\Controller;

use App\NewsReview\Domain\Repository\PopularPublicationRepositoryInterface;
use App\PublishersList\Controller\Exception\InvalidRequestException;
use App\Twitter\Infrastructure\Api\AccessToken\Repository\TokenRepository;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Infrastructure\Cache\RedisCache;
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

class ListController
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
     * @param Client $client
     * @return bool
     */
    function shouldRefreshCacheForAggregates(Client $client)
    {
        return $this->shouldRefreshCacheFor($client, 'aggregates');
    }

    /**
     * @param Client $client
     * @return bool
     */
    function shouldRefreshCacheForMembers(Client $client)
    {
        return $this->shouldRefreshCacheFor($client, 'members');
    }

    /**
     * @param Client $client
     * @param string $key
     * @param string $prefix
     * @return bool
     */
    function shouldRefreshCacheFor(Client $client, string $prefix)
    {
        return $client->get($prefix.'.recent_delete') ||
            $client->get($prefix.'.recent_statuses_collect') ||
            $client->get($prefix.'.total_statuses_reset');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getHighlights(Request $request)
    {
        return $this->getCollection(
            $request,
            counter: function (SearchParams $searchParams) use ($request) {
                return $this->getTotalPages($searchParams, $request);
            },
            finder: function (SearchParams $searchParams) {
                return $this->getHighlightsFromSearchParams($searchParams);
            },
            params: [
                'aggregate' => 'string',
                'endDate' => 'datetime',
                'includeRetweets' => 'bool',
                'routeName' => 'string',
                'selectedAggregates' => 'array',
                'startDate' => 'datetime',
                'term' => 'string',
            ]
        );
    }

    /**
     * @param SearchParams $searchParams
     * @param Request      $request
     * @return bool|int|string|JsonResponse
     * @throws NonUniqueResultException
     */
    private function getTotalPages(SearchParams $searchParams, Request $request) {
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

    /**
     * @return bool
     */
    private function notInProduction(): bool
    {
        return $this->environment !== 'prod';
    }

    /**
     * @param string       $prefix
     * @param SearchParams $searchParams
     * @return string
     */
    public function getCacheKey(string $prefix, SearchParams $searchParams): string
    {
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
                $term
            ]
        );
    }

    /**
     * @param SearchParams $searchParams
     * @return bool
     */
    private function invalidHighlightsSearchParams(SearchParams $searchParams): bool
    {
        return !\array_key_exists('startDate', $searchParams->getParams()) ||
            (!($searchParams->getParams()['startDate'] instanceof \DateTime)) ||
            !\array_key_exists('endDate', $searchParams->getParams()) ||
            (!($searchParams->getParams()['endDate'] instanceof \DateTime)) ||
            !\array_key_exists('includeRetweets', $searchParams->getParams());
    }

    /**
     * @param Request  $request
     * @param callable $counter
     * @param callable $finder
     * @param array    $params
     * @return JsonResponse
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

        $searchParams = SearchParams::fromRequest($request, $params);

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

        if ($searchParams->getPageIndex() > $totalPagesOrResponse) {
            $highlightUrl = $this->router->generate('highlight');
            $response = $this->makeOkResponse([]);

            if ($request->getPathInfo() === $highlightUrl) {
                $response = $this->makeOkResponse([
                    'aggregates' => $this->highlightRepository->selectDistinctAggregates($searchParams),
                ]);
            }

            $response->headers->add($totalPagesHeader);
            $response->headers->add($pageIndexHeader);

            return $response;
        }

        $items = $finder($searchParams);

        $response = $this->makeOkResponse($items);
        $response->headers->add($totalPagesHeader);
        $response->headers->add($pageIndexHeader);

        return $response;
    }

    /**
     * @param $data
     * @return JsonResponse
     */
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

    /**
     * @param Request $request
     *
     * @return array|JsonResponse
     * @throws InvalidRequestException
     * @throws NonUniqueResultException
     */
    private function guardAgainstMissingRequirementsForStatusCollection(Request $request)
    {
        $corsHeaders = $this->getAccessControlOriginHeaders(
            $this->environment,
            $this->allowedOrigin
        );

        $decodedContent = $this->guardAgainstInvalidParametersEncoding($request, $corsHeaders);
        $decodedContent = $this->guardAgainstInvalidParameters($decodedContent, $corsHeaders);

        return [
            'token' => $this->guardAgainstInvalidAuthenticationToken($corsHeaders),
            'member_name' => $this->guardAgainstMissingMemberName($decodedContent, $corsHeaders),
            'aggregate_id' => $this->guardAgainstMissingAggregateId($decodedContent, $corsHeaders)
        ];
    }

    /**
     * @param Request $request
     *
     * @return array|JsonResponse
     * @throws InvalidRequestException
     * @throws NonUniqueResultException
     */
    private function guardAgainstMissingRequirementsForBulkStatusCollection(Request $request)
    {
        $corsHeaders = $this->getAccessControlOriginHeaders(
            $this->environment,
            $this->allowedOrigin
        );

        $decodedContent = $this->guardAgainstInvalidParametersEncoding($request, $corsHeaders);
        $decodedContent = $this->guardAgainstInvalidParameters($decodedContent, $corsHeaders);
        $aggregateId = $this->guardAgainstMissingAggregateId($decodedContent, $corsHeaders);
        $membersNames = $this->guardAgainstMissingMembersNames($decodedContent, $corsHeaders);

        return [
            'token' => $this->guardAgainstInvalidAuthenticationToken($corsHeaders),
            'aggregate_id' => $aggregateId,
            'members_names' => $membersNames
        ];
    }

    /**
     * @param $decodedContent
     * @param $corsHeaders
     *
     * @return int
     * @throws InvalidRequestException
     */
    private function guardAgainstMissingAggregateId($decodedContent, $corsHeaders): int
    {
        if (!array_key_exists('aggregateId', $decodedContent['params']) ||
            (int) $decodedContent['params']['aggregateId'] < 1) {
            $exceptionMessage = 'Invalid aggregated id';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );
            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return (int) $decodedContent['params']['aggregateId'];
    }

    /**
     * @param $decodedContent
     * @param $corsHeaders
     *
     * @return mixed
     * @throws InvalidRequestException
     */
    private function guardAgainstMissingMemberName($decodedContent, $corsHeaders): string
    {
        if (!\array_key_exists('memberName', $decodedContent['params'])) {
            $exceptionMessage = 'Invalid member name';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );
            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $decodedContent['params']['memberName'];
    }

    /**
     * @param $decodedContent
     * @param $corsHeaders
     *
     * @return mixed
     * @throws InvalidRequestException
     */
    private function guardAgainstMissingMembersNames($decodedContent, $corsHeaders): array
    {
        if (!array_key_exists('membersNames', $decodedContent['params'])) {
            $exceptionMessage = 'Invalid members names';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );
            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $decodedContent['params']['membersNames'];
    }

    /**
     * @param Request $request
     * @param         $corsHeaders
     *
     * @return mixed
     * @throws InvalidRequestException
     */
    private function guardAgainstInvalidParametersEncoding(Request $request, $corsHeaders): array
    {
        $decodedContent = json_decode($request->getContent(), $asArray = true);
        $lastError = json_last_error();
        if ($lastError !== JSON_ERROR_NONE) {
            $exceptionMessage = 'Invalid parameters encoding';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );
            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $decodedContent;
    }

    /**
     * @param $decodedContent
     * @param $corsHeaders
     *
     * @return mixed
     * @throws InvalidRequestException
     */
    private function guardAgainstInvalidParameters($decodedContent, $corsHeaders): array
    {
        if (!array_key_exists('params', $decodedContent) ||
            !is_array($decodedContent['params'])) {
            $exceptionMessage = 'Invalid params';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );
            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $decodedContent;
    }

    /**
     * @param $corsHeaders
     *
     * @return Token
     * @throws InvalidRequestException
     * @throws NonUniqueResultException
     */
    private function guardAgainstInvalidAuthenticationToken($corsHeaders): TokenInterface
    {
        $token = $this->tokenRepository->findFirstUnfrozenToken();
        if (!($token instanceof Token)) {
            $exceptionMessage = 'Could not process your request at the moment';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                503,
                $corsHeaders
            );
            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $token;
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    private function makeJsonResponse($message = 'Your request should be processed very soon'): JsonResponse
    {
        return new JsonResponse(
            $message,
            200,
            $this->getAccessControlOriginHeaders(
                $this->environment,
                $this->allowedOrigin
            )
        );
    }
}

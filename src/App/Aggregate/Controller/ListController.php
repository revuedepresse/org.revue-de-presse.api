<?php

namespace App\Aggregate\Controller;

use App\Aggregate\Repository\TimelyStatusRepository;
use App\Cache\RedisCache;
use App\Member\Authentication\Authenticator;
use App\Member\MemberInterface;
use App\Member\Repository\AuthenticationTokenRepository;
use App\Security\Cors\CorsHeadersAwareTrait;
use App\Status\Repository\HighlightRepository;
use Doctrine\ORM\NonUniqueResultException;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Predis\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository;
use WTW\UserBundle\Repository\UserRepository;

class ListController
{
    use CorsHeadersAwareTrait;

    /**
     * @var AuthenticationTokenRepository
     */
    public $authenticationTokenRepository;

    /**
     * @var TokenRepository
     */
    public $tokenRepository;

    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @var HighlightRepository
     */
    public $highlightRepository;

    /**
     * @var TimelyStatusRepository
     */
    public $timelyStatusRepository;

    /**
     * @var Producer
     */
    public $aggregateStatusesProducer;

    /**
     * @var Producer
     */
    public $aggregateLikesProducer;

    /**
     * @var string
     */
    public $environment;

    /**
     * @var string
     */
    public $allowedOrigin;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var RedisCache
     */
    public $redisCache;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAggregates(Request $request)
    {
        $client = $this->redisCache->getClient();

        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) use ($client) {
                $key = 'aggregates.total_pages.'.$searchParams->getFingerprint();

                $totalPages = $client->get($key);

                if ($this->shouldRefreshCache($client) && $totalPages) {
                    $client->del($key);
                    $totalPages = null;
                }

                if (is_null($totalPages)) {
                    $totalPages = $this->aggregateRepository->countTotalPages($searchParams);
                    $client->set($key, $totalPages);
                }

                return $totalPages;
            },
            $finder = function (SearchParams $searchParams) use ($client) {
                $key = 'aggregates.items.'.$searchParams->getFingerprint();
                $aggregates = $client->get($key);

                if ($this->shouldRefreshCache($client) && $aggregates) {
                    $client->del($key);

                    if ($client->get('aggregates.recent_delete')) {
                        $client->del('aggregates.recent_delete');
                    }
                    if ($client->get('aggregates.recent_statuses_collect')) {
                        $client->del('aggregates.recent_statuses_collect');
                    }
                    if ($client->get('aggregates.total_statuses_reset')) {
                        $client->del('aggregates.total_statuses_reset');
                    }

                    $aggregates = null;
                }

                if (is_null($aggregates)) {
                    $aggregates = json_encode($this->aggregateRepository->findAggregates($searchParams));
                    $client->set($key, $aggregates);
                }

                return json_decode($aggregates, true);
            }
        );
    }

    /**
     * @param Client $client
     * @return bool
     */
    function shouldRefreshCache(Client $client)
    {
        return $client->get('aggregates.recent_delete') ||
            $client->get('aggregates.recent_statuses_collect') ||
            $client->get('aggregates.total_statuses_reset');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getHighlights(Request $request)
    {
        $client = $this->redisCache->getClient();

        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) use ($client, $request) {
                $unauthorizedJsonResponse = new JsonResponse(
                    'Unauthorized request',
                    403,
                    $this->getAccessControlOriginHeaders($this->environment, $this->allowedOrigin)
                );

                if ($this->invalidHighlightsSearchParams($searchParams)) {
                    return $unauthorizedJsonResponse;
                }

                $queriedRouteAccess = $searchParams->hasParam('routeName');
                if ($queriedRouteAccess && !$request->headers->has('x-auth-admin-token')) {
                    return $unauthorizedJsonResponse;
                }

                if ($queriedRouteAccess) {
                    $tokenId = $request->headers->get('x-auth-admin-token');
                    $memberProperties = $this->authenticationTokenRepository->findByTokenIdentifier($tokenId);

                    if (!($memberProperties['member'] instanceof MemberInterface)) {
                        return $unauthorizedJsonResponse;
                    }
                }

                $key = $this->getCacheKey('highlights.total_pages', $searchParams);
                $totalPages = $client->get($key);

                if (!$totalPages || $this->notInProduction()) {
                    $totalPages = $this->highlightRepository->countTotalPages($searchParams);
                    $client->setex($key, 3600, $totalPages);
                }

                return $totalPages;
            },
            $finder = function (SearchParams $searchParams) use ($client) {
                if ($this->invalidHighlightsSearchParams($searchParams)) {
                    return [];
                }

                $key = $this->getCacheKey('highlights.items', $searchParams);
                $highlights = $client->get($key);

                if (!$highlights || $this->notInProduction()) {
                    $highlights = json_encode($this->highlightRepository->findHighlights($searchParams));
                    $client->setex($key, 3600, $highlights);
                }

                return json_decode($highlights, true);
            },
            [
                'date' => 'datetime',
                'includeRetweets' => 'bool',
                'aggregate' => 'string',
                'routeName' => 'string'
            ]
        );
    }

    /**
     * @param $this
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

        return implode([
            $prefix,
            $searchParams->getParams()['date']->format('Y-m-d H'),
            $includedRetweets
        ]);
    }

    /**
     * @param SearchParams $searchParams
     * @return bool
     */
    private function invalidHighlightsSearchParams(SearchParams $searchParams): bool
    {
        return !array_key_exists('date', $searchParams->getParams()) ||
            ($searchParams->getParams() instanceof \DateTime) ||
            !array_key_exists('includeRetweets', $searchParams->getParams());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMembers(Request $request)
    {
        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) {
                return $this->memberRepository->countTotalPages($searchParams);
            },
            $finder = function (SearchParams $searchParams) {
                return $this->memberRepository->findMembers($searchParams);
            },
            ['aggregateId' => 'int']
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatuses(Request $request)
    {
        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) {
                return $this->timelyStatusRepository->countTotalPages($searchParams);
            },
            $finder = function (SearchParams $searchParams) {
                return $this->timelyStatusRepository->findStatuses($searchParams);
            },
            ['memberName' => 'string']
        );
    }

    /**
     * @param Request  $request
     * @param callable $counter
     * @param callable $finder
     * @param array    $params
     * @return JsonResponse
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
            $totalPages = $counter($searchParams);
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

        $totalPagesHeader = ['x-total-pages' => $totalPages];
        $pageIndexHeader = ['x-page-index' => $searchParams->getPageIndex()];

        if ($searchParams->getPageIndex() > $totalPages) {
            $response = $this->makeOkResponse([]);
            $response->headers->add($totalPagesHeader);
            $response->headers->add($pageIndexHeader);

            return $response;
        }

        $aggregates = $finder($searchParams);

        $response = $this->makeOkResponse($aggregates);
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
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function requestCollection(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $requirementsOrJsonResponse = $this->guardAgainstMissingRequirements($request);
        if ($requirementsOrJsonResponse instanceof JsonResponse) {
            return $requirementsOrJsonResponse;
        }

        /** @var Token $token */
        $token = $requirementsOrJsonResponse['token'];

        $messageBody = [
            'token' => $token->getOauthToken(),
            'secret' => $token->getOauthTokenSecret(),
            'consumer_token' => $token->consumerKey,
            'consumer_secret' => $token->consumerSecret
        ];
        $messageBody['screen_name'] = $requirementsOrJsonResponse['member_name'];
        $messageBody['aggregate_id'] = $requirementsOrJsonResponse['aggregate_id'];


        $this->aggregateLikesProducer->setContentType('application/json');
        $this->aggregateLikesProducer->publish(serialize(json_encode($messageBody)));

        $this->aggregateStatusesProducer->setContentType('application/json');
        $this->aggregateStatusesProducer->publish(serialize(json_encode($messageBody)));

        return new JsonResponse(
            'Your request should be processed very soon',
            200,
            $this->getAccessControlOriginHeaders(
                $this->environment,
                $this->allowedOrigin
            )
        );
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unlockAggregate(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $requirementsOrJsonResponse = $this->guardAgainstMissingRequirements($request);
        if ($requirementsOrJsonResponse instanceof JsonResponse) {
            return $requirementsOrJsonResponse;
        }

        /**
         * @var Aggregate $aggregate
         */
        $aggregate = $this->aggregateRepository->findOneBy([
           'screenName' => $requirementsOrJsonResponse['member_name'],
           'id' => $requirementsOrJsonResponse['aggregate_id'],
        ]);

        if (!($aggregate instanceof Aggregate)) {
            return new JsonResponse(
                'Invalid aggregate',
                422,
                $this->getAccessControlOriginHeaders(
                    $this->environment,
                    $this->allowedOrigin
                )
            );
        }

        $this->aggregateRepository->unlockAggregate($aggregate);

        return new JsonResponse(
            'Your request should be processed very soon',
            200,
            $this->getAccessControlOriginHeaders(
                $this->environment,
                $this->allowedOrigin
            )
        );
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
     * @throws NonUniqueResultException
     */
    private function guardAgainstMissingRequirements(Request $request)
    {
        $corsHeaders = $this->getAccessControlOriginHeaders(
            $this->environment,
            $this->allowedOrigin
        );

        $decodedContent = json_decode($request->getContent(), $asArray = true);
        $lastError = json_last_error();
        if ($lastError !== JSON_ERROR_NONE) {
            return new JsonResponse(
                'Invalid parameters encoding',
                422,
                $corsHeaders
            );
        }

        if (!array_key_exists('params', $decodedContent) ||
            !is_array($decodedContent['params'])) {
            return new JsonResponse(
                'Invalid params',
                422,
                $corsHeaders
            );
        }

        if (!array_key_exists('aggregateId', $decodedContent['params']) ||
            intval($decodedContent['params']['aggregateId']) < 1) {
            return new JsonResponse(
                'Invalid aggregated id',
                422,
                $corsHeaders
            );
        }
        $aggregateId = intval($decodedContent['params']['aggregateId']);

        if (!array_key_exists('memberName', $decodedContent['params'])) {
            return new JsonResponse(
                'Invalid member name',
                422,
                $corsHeaders
            );
        }
        $memberName = $decodedContent['params']['memberName'];

        $token = $this->tokenRepository->findFirstUnfrozenToken();
        if (!($token instanceof Token)) {
            return new JsonResponse(
                'Could not process your request at the moment',
                503,
                $corsHeaders
            );
        }

        return [
            'token' => $token,
            'member_name' => $memberName,
            'aggregate_id' => $aggregateId
        ];
    }
}

<?php

namespace App\Aggregate\Controller;

use App\Aggregate\Controller\Exception\InvalidRequestException;
use App\Aggregate\Repository\TimelyStatusRepository;
use App\Cache\RedisCache;
use App\Http\SearchParams;
use App\Member\MemberInterface;
use App\Member\Repository\AuthenticationTokenRepository;
use App\RequestValidation\RequestParametersValidationTrait;
use App\Security\AuthenticationTokenValidationTrait;
use App\Security\Cors\CorsHeadersAwareTrait;
use App\Security\Exception\UnauthorizedRequestException;
use App\Security\HttpAuthenticator;
use App\Status\Repository\HighlightRepository;
use Doctrine\ORM\NonUniqueResultException;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Predis\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;
use WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository;
use WTW\UserBundle\Repository\UserRepository;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class ListController
{
    use AuthenticationTokenValidationTrait;
    use CorsHeadersAwareTrait;
    use RequestParametersValidationTrait;
    /**
     * @var AuthenticationTokenRepository
     */
    public $authenticationTokenRepository;

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
    public $configDirectory;

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
     * @var RouterInterface
     */
    public $router;

    /**
     * @var HttpAuthenticator
     */
    public $httpAuthenticator;

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAggregates(Request $request)
    {
        $client = $this->redisCache->getClient();

        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) use ($client) {
                $key = 'aggregates.total_pages.'.$searchParams->getFingerprint();

                $totalPages = $client->get($key);

                if ($this->shouldRefreshCacheForAggregates($client) && $totalPages) {
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

                if ($this->shouldRefreshCacheForAggregates($client) && $aggregates) {
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getHighlights(Request $request)
    {
        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) use ($request) {
                return $this->getTotalPages($searchParams, $request);
            },
            $finder = function (SearchParams $searchParams) {
                return $this->getHighlightsFromSearchParams($searchParams);
            },
            [
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

        $queriedRouteAccess = $searchParams->hasParam('routeName');
        if ($queriedRouteAccess) {
            try {
                $this->httpAuthenticator->authenticateMember($request);
            } catch (UnauthorizedRequestException $exception) {
                return $unauthorizedJsonResponse;
            }
        }

        $key = $this->getCacheKey('highlights.total_pages', $searchParams);

        if (!$searchParams->hasParam('selectedAggregates') && !$queriedRouteAccess) {
            return 1;
        }

        $client = $this->redisCache->getClient();
        $totalPages = $client->get($key);

        if (!$totalPages || $this->notInProduction()) {
            $totalPages = $this->highlightRepository->countTotalPages($searchParams);
            $client->setex($key, 3600, $totalPages);
        }

        return $totalPages;
    }

    /**
     * @param $searchParams
     * @return array|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getHighlightsFromSearchParams(SearchParams $searchParams) {
        if ($this->invalidHighlightsSearchParams($searchParams)) {
            return [];
        }

        $queriedRouteAccess = $searchParams->hasParam('routeName');

        $hasChild = false;
        if (!$searchParams->hasParam('selectedAggregates') && !$queriedRouteAccess) {
            $snapshot = $this->getFirebaseDatabaseSnapshot($searchParams);
            $hasChild = $snapshot->hasChildren();
        }

        $client = $this->redisCache->getClient();

        if (!$hasChild) {
            $key = $this->getCacheKey('highlights.items', $searchParams);

            $highlights = $client->get($key);
            if (!$highlights || $this->notInProduction()) {
                $highlights = json_encode($this->highlightRepository->findHighlights($searchParams));
                $client->setex($key, 3600, $highlights);
            }

            return json_decode($highlights, true);
        }

        return $this->getHighlightsFromFirebaseSnapshot($searchParams, $snapshot, $client);
    }

    /**
     * @param SearchParams $searchParams
     * @param              $snapshot
     * @param              $client
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getHighlightsFromFirebaseSnapshot(SearchParams $searchParams, $snapshot, Client $client): array
    {
        $key = $this->getCacheKey('highlights.items', $searchParams);

        $highlights = array_reverse($snapshot->getValue());
        $highlights = array_map(function (array $highlight) {
            return [
                'original_document' => $highlight['json'],
                'id' => $highlight['id'],
                'publicationDateTime' => $highlight['publishedAt'],
                'screen_name' => $highlight['username'],
                'last_update' => $highlight['checkedAt'],
                'total_retweets' => $highlight['totalRetweets'],
                'total_favorites' => $highlight['totalFavorites'],
            ];
        }, $highlights);
        $statuses = $this->highlightRepository->mapStatuses($searchParams, $highlights);

        $cachedHighlights = [
            'aggregates' => [],
            'statuses' => $statuses,
        ];
        $client->setex($key, 3600, json_encode($cachedHighlights));

        return $cachedHighlights;
    }

    /**
     * @param SearchParams $searchParams
     * @return mixed
     */
    private function getFirebaseDatabaseSnapshot(SearchParams $searchParams)
    {
        $database = $this->getFirebaseDatabase();

        $aggregateId = null;
        if ($searchParams->hasParam('aggregate')) {
            $aggregateId = $this->aggregateRepository->findOneBy([
                'name' => $searchParams->getParams()['aggregate']
            ]);
        }
        if (is_null($aggregateId)) {
            $aggregateId = 1;
        }

        $path = '/'.implode(
            '/',
            [
                'highlights',
                $aggregateId,
                $searchParams->getParams()['startDate']->format('Y-m-d'),
                $searchParams->getParams()['includeRetweets'] ? 'retweet' : 'status'
            ]
        );
        $this->logger->info(sprintf('Firebase Path: %s', $path));
        $reference = $database->getReference($path);

        return $reference
            ->orderByChild('totalRetweets')
            ->getSnapshot();
    }

    /**
     * @return mixed
     */
    public function getFirebaseDatabase()
    {
        $serviceAccount = ServiceAccount::fromJsonFile(
            $this->configDirectory . '/google-service-account.json'
        );

        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            // The following line is optional if the project id in your credentials file
            // is identical to the subdomain of your Firebase project. If you need it,
            // make sure to replace the URL with the URL of your project.
            ->withDatabaseUri('https://weaving-the-web-6fe11.firebaseio.com')
            ->create();

        return $firebase->getDatabase();
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
        return !array_key_exists('startDate', $searchParams->getParams()) ||
            (!($searchParams->getParams()['startDate'] instanceof \DateTime)) ||
            !array_key_exists('endDate', $searchParams->getParams()) ||
            (!($searchParams->getParams()['endDate'] instanceof \DateTime)) ||
            !array_key_exists('includeRetweets', $searchParams->getParams());
    }

    /**
     * @param Request $request
     * @return int|null|JsonResponse
     * @throws NonUniqueResultException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMembers(Request $request)
    {
        $client = $this->redisCache->getClient();

        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) use ($client) {
                $key = 'members.total_pages.'.$searchParams->getFingerprint();
                $totalPages = $client->get($key);

                if ($this->shouldRefreshCacheForMembers($client) && $totalPages) {
                    $client->del($key);
                    $totalPages = null;
                }

                if (is_null($totalPages)) {
                    $totalPages = $this->memberRepository->countTotalPages($searchParams);
                    $client->set($key, $totalPages);
                }

                return intval($totalPages);
            },
            $finder = function (SearchParams $searchParams) use ($client) {
                $key = 'members.items.'.$searchParams->getFingerprint();

                $members = $client->get($key);

                if ($this->shouldRefreshCacheForMembers($client) && $members) {
                    $client->del($key);

                    if ($client->get('members.recent_delete')) {
                        $client->del('members.recent_delete');
                    }

                    if ($client->get('members.recent_statuses_collect')) {
                        $client->del('members.recent_statuses_collect');
                    }

                    if ($client->get('members.total_statuses_reset')) {
                        $client->del('members.total_statuses_reset');
                    }

                    $members = null;
                }

                if (is_null($members)) {
                    $members = json_encode($this->memberRepository->findMembers($searchParams));
                    $client->set($key, $members);
                }

                return $members;
            },
            ['aggregateId' => 'int']
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
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
     * @throws \Doctrine\DBAL\DBALException
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
     * @return array|JsonResponse
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function bulkCollectAggregatesStatuses(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $requirementsOrJsonResponse = $this->guardAgainstMissingRequirementsForBulkStatusCollection($request);
        if ($requirementsOrJsonResponse instanceof JsonResponse) {
            return $requirementsOrJsonResponse;
        }

        array_walk(
            $requirementsOrJsonResponse['members_names'],
            function ($memberName) use ($requirementsOrJsonResponse) {
                $requirements = [
                    'token' => $requirementsOrJsonResponse['token'],
                    'member_name' => $memberName,
                    'aggregate_id' => $requirementsOrJsonResponse['aggregate_id']
                ];
                $this->aggregateRepository->resetTotalStatusesForAggregateRelatedToScreenName($memberName);

                $this->produceCollectionRequestFromRequirements($requirements);
            }
        );

        return $this->makeJsonResponse();
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

        $requirementsOrJsonResponse = $this->guardAgainstMissingRequirementsForStatusCollection($request);
        if ($requirementsOrJsonResponse instanceof JsonResponse) {
            return $requirementsOrJsonResponse;
        }

        $this->produceCollectionRequestFromRequirements($requirementsOrJsonResponse);

        return $this->makeJsonResponse();
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

        $requirementsOrJsonResponse = $this->guardAgainstMissingRequirementsForStatusCollection($request);
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

        return $this->makeJsonResponse();
    }

    /**
     * @param Request $request
     * @return array|JsonResponse
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
     * @return array|JsonResponse
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
     * @return int
     */
    private function guardAgainstMissingAggregateId($decodedContent, $corsHeaders): int
    {
        if (!array_key_exists('aggregateId', $decodedContent['params']) ||
            intval($decodedContent['params']['aggregateId']) < 1) {
            $exceptionMessage = 'Invalid aggregated id';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                422,
                $corsHeaders
            );
            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return intval($decodedContent['params']['aggregateId']);
    }

    /**
     * @param $decodedContent
     * @param $corsHeaders
     * @return mixed
     */
    private function guardAgainstMissingMemberName($decodedContent, $corsHeaders): string
    {
        if (!array_key_exists('memberName', $decodedContent['params'])) {
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
     * @return mixed
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
     * @param $requirementsOrJsonResponse
     */
    private function produceCollectionRequestFromRequirements($requirementsOrJsonResponse): void
    {
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

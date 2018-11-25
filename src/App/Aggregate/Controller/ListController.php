<?php

namespace App\Aggregate\Controller;

use App\Aggregate\Repository\TimelyStatusRepository;
use App\Security\Cors\CorsHeadersAwareTrait;
use Doctrine\ORM\NonUniqueResultException;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PHPUnit\Util\Json;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAggregates(Request $request)
    {
        return $this->getCollection(
            $request,
            $counter = function (SearchParams $searchParams) {
                return $this->aggregateRepository->countTotalPages($searchParams);
            },
            $finder = function (SearchParams $searchParams) {
                return $this->aggregateRepository->findAggregates($searchParams);
            }
        );
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

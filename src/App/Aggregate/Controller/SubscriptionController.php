<?php

namespace App\Aggregate\Controller;

use App\Cache\RedisCache;
use App\Http\PaginationParams;
use App\Member\Exception\InvalidMemberException;
use App\Member\MemberInterface;
use App\Member\Repository\MemberSubscriptionRepository;
use App\Security\AuthenticationTokenValidationTrait;
use App\Security\Cors\CorsHeadersAwareTrait;
use App\Security\Exception\UnauthorizedRequestException;
use App\Security\HttpAuthenticator;
use App\StatusCollection\Messaging\Exception\InvalidMemberAggregate;
use App\StatusCollection\Messaging\MessagePublisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeavingTheWeb\Bundle\ApiBundle\Entity\AggregateIdentity;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use WTW\UserBundle\Repository\UserRepository;

class SubscriptionController
{
    use CorsHeadersAwareTrait;
    use AuthenticationTokenValidationTrait;

    /**
     * @var string
     */
    public $allowedOrigin;

    /**
     * @var string
     */
    public $environment;

    /**
     * @var RedisCache
     */
    public $redisCache;

    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @var MemberSubscriptionRepository
     */
    public $memberSubscriptionRepository;

    /**
     * @var MessagePublisher
     */
    public $messagePublisher;

    /**
     * @var HttpAuthenticator
     */
    public $httpAuthenticator;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMemberSubscriptions(Request $request): JsonResponse
    {
        $memberOrJsonResponse = $this->authenticateMember($request);

        if ($memberOrJsonResponse instanceof JsonResponse) {
            return $memberOrJsonResponse;
        }

        $paginationParams = PaginationParams::fromRequest($request);

        $aggregateIdentity = null;
        if ($request->get('aggregateId')) {
            $aggregateIdentity = new AggregateIdentity(intval($request->get('aggregateId')));
        }

        $client = $this->redisCache->getClient();
        $cacheKey = $this->getCacheKey($memberOrJsonResponse, $paginationParams, $aggregateIdentity);
        $memberSubscriptions = $client->get($cacheKey);

        if (!$memberSubscriptions) {
            $memberSubscriptions = $this->memberSubscriptionRepository->getMemberSubscriptions(
                $memberOrJsonResponse,
                $paginationParams,
                $aggregateIdentity
            );
            $memberSubscriptions = json_encode($memberSubscriptions);
            $client->setex($cacheKey, 3600, $memberSubscriptions);
        }

        $memberSubscriptions = json_decode($memberSubscriptions, $asArray = true);

        return new JsonResponse(
            $memberSubscriptions['subscriptions'],
            200,
            array_merge(
                $this->getAccessControlOriginHeaders($this->environment, $this->allowedOrigin),
                [
                    'x-total-pages' => $memberSubscriptions['total_subscriptions'],
                    'x-page-index' => $paginationParams->pageIndex
                ]
            )
        );
    }

    /**
     * @param MemberInterface   $member
     * @param PaginationParams  $paginationParams
     * @param AggregateIdentity $aggregateIdentity
     * @return string
     */
    private function getCacheKey(
        MemberInterface $member,
        PaginationParams $paginationParams,
        AggregateIdentity $aggregateIdentity = null
    ): string {
        return sprintf(
            '%s:%s:%s/%s',
            $aggregateIdentity ?: '',
            $member->getId(),
            $paginationParams->pageSize,
            $paginationParams->pageIndex
        );
    }

    /**
     * @param Request $request
     * @return MemberInterface|JsonResponse|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function requestMemberSubscriptionStatusCollection(Request $request): JsonResponse
    {
        $memberOrJsonResponse = $this->authenticateMember($request);

        if ($memberOrJsonResponse instanceof JsonResponse) {
            return $memberOrJsonResponse;
        }

        $memberSubscriptions = $this->memberSubscriptionRepository->getMemberSubscriptions($memberOrJsonResponse);

        /** @var Token $token */
        $token = $this->guardAgainstInvalidAuthenticationToken($this->getCorsOptionsResponse(
            $this->environment,
            $this->allowedOrigin
        ));

        array_walk(
            $memberSubscriptions['subscriptions']['subscriptions'],
            function (array $subscription) use ($token) {
                try {
                    $member = InvalidMemberException::ensureMemberHavingUsernameIsAvailable(
                        $this->memberRepository,
                        $subscription['username']
                    );
                    $this->messagePublisher->publishMemberAggregateMessage($member, $token);
                } catch (InvalidMemberException|InvalidMemberAggregate $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        );

        return new JsonResponse('', 204, []);
    }

    /**
     * @param Request $request
     * @return MemberInterface|JsonResponse|null
     */
    private function authenticateMember(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $corsHeaders = $this->getAccessControlOriginHeaders($this->environment, $this->allowedOrigin);
        $unauthorizedJsonResponse = new JsonResponse(
            'Unauthorized request',
            403,
            $corsHeaders
        );

        try {
            return $this->httpAuthenticator->authenticateMember($request);
        } catch (UnauthorizedRequestException $exception) {
            return $unauthorizedJsonResponse;
        }
    }
}

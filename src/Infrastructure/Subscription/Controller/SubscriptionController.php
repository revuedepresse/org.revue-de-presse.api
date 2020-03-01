<?php
declare(strict_types=1);

namespace App\Infrastructure\Subscription\Controller;

use App\Cache\RedisCache;
use App\Domain\Membership\Exception\InvalidMemberException;
use App\Http\PaginationParams;
use App\Infrastructure\Amqp\MessageBus\PublicationListDispatcherInterface;
use App\Infrastructure\DependencyInjection\Publication\PublicationListDispatcherTrait;
use App\Infrastructure\Repository\Membership\MemberRepositoryInterface;
use App\Infrastructure\Repository\Subscription\MemberSubscriptionRepositoryInterface;
use App\Infrastructure\Security\Authentication\AuthenticationTokenValidationTrait;
use App\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use App\Member\MemberInterface;
use App\Security\Exception\UnauthorizedRequestException;
use App\Security\HttpAuthenticator;
use App\StatusCollection\Messaging\Exception\InvalidMemberAggregate;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WeavingTheWeb\Bundle\ApiBundle\Entity\AggregateIdentity;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use function array_merge;
use function array_walk;
use function boolval;
use function is_null;
use function json_decode;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

class SubscriptionController
{
    use AuthenticationTokenValidationTrait;
    use CorsHeadersAwareTrait;
    use PublicationListDispatcherTrait;

    public RedisCache $redisCache;

    public MemberRepositoryInterface $memberRepository;

    public MemberSubscriptionRepositoryInterface $memberSubscriptionRepository;

    public HttpAuthenticator $httpAuthenticator;

    public LoggerInterface $logger;

    public function getMemberSubscriptions(Request $request): JsonResponse
    {
        $memberOrJsonResponse = $this->authenticateMember($request);
        if ($memberOrJsonResponse instanceof JsonResponse) {
            return $memberOrJsonResponse;
        }

        $memberSubscriptions = $this->getCachedMemberSubscriptions(
            $request,
            $memberOrJsonResponse
        );
        if (!$memberSubscriptions) {
            $memberSubscriptions = $this->memberSubscriptionRepository
                ->getMemberSubscriptions(
                    $memberOrJsonResponse,
                    $request
                );
            $memberSubscriptions = json_encode(
                $memberSubscriptions,
                JSON_THROW_ON_ERROR
            );

            $client   = $this->redisCache->getClient();
            $cacheKey = $this->getCacheKey($memberOrJsonResponse, $request);
            $client->setex($cacheKey, 3600, $memberSubscriptions);
        }

        $memberSubscriptions = json_decode(
            $memberSubscriptions,
            $asArray = true,
            512,
            JSON_THROW_ON_ERROR
        );
        $paginationParams    = PaginationParams::fromRequest($request);

        return new JsonResponse(
            $memberSubscriptions['subscriptions'],
            200,
            array_merge(
                $this->getAccessControlOriginHeaders(
                    $this->environment,
                    $this->allowedOrigin
                ),
                [
                    'x-total-pages' => $memberSubscriptions['total_subscriptions'],
                    'x-page-index'  => $paginationParams->pageIndex
                ]
            )
        );
    }

    private function getCacheKey(
        MemberInterface $member,
        Request $request
    ): string {
        $paginationParams  = PaginationParams::fromRequest($request);
        $aggregateIdentity = AggregateIdentity::fromRequest($request);

        return sprintf(
            '%s:%s:%s/%s',
            $aggregateIdentity ?: '',
            $member->getId(),
            $paginationParams->pageSize,
            $paginationParams->pageIndex
        );
    }

    public function requestMemberSubscriptionStatusCollection(Request $request): JsonResponse
    {
        $memberOrJsonResponse = $this->authenticateMember($request);

        if ($memberOrJsonResponse instanceof JsonResponse) {
            return $memberOrJsonResponse;
        }

        $memberSubscriptions = $this->memberSubscriptionRepository->getMemberSubscriptions($memberOrJsonResponse);

        /** @var Token $token */
        $token = $this->guardAgainstInvalidAuthenticationToken(
            $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            )
        );

        array_walk(
            $memberSubscriptions['subscriptions']['subscriptions'],
            function (array $subscription) use ($token) {
                try {
                    $member = InvalidMemberException::ensureMemberHavingUsernameIsAvailable(
                        $this->memberRepository,
                        $subscription['username']
                    );
                    $this->messageDispatcher->dispatchMemberPublicationListMessage(
                        $member,
                        $token
                    );
                } catch (InvalidMemberException|InvalidMemberAggregate $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        );

        return new JsonResponse('', 204, []);
    }

    private function authenticateMember(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->getCorsOptionsResponse(
                $this->environment,
                $this->allowedOrigin
            );
        }

        $corsHeaders              = $this->getAccessControlOriginHeaders($this->environment, $this->allowedOrigin);
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

    private function willCacheResponse(Request $request): bool
    {
        $willCacheResponse = true;
        if (
            $request->headers->has('x-no-cache')
            && $request->headers->get('x-no-cache')
        ) {
            $willCacheResponse = !boolval($request->headers->get('x-no-cache'));
        }

        return $willCacheResponse;
    }

    private function getCachedMemberSubscriptions(
        Request $request,
        $memberOrJsonResponse
    ): string {
        $memberSubscriptions = '';
        if ($this->willCacheResponse($request)) {
            $client              = $this->redisCache->getClient();
            $cacheKey            = $this->getCacheKey($memberOrJsonResponse, $request);
            $memberSubscriptions = $client->get($cacheKey);
            if (is_null($memberSubscriptions)) {
                $memberSubscriptions = '';
            }
        }

        return $memberSubscriptions;
    }
}

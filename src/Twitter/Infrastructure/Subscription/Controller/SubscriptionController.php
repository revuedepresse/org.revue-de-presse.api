<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Subscription\Controller;

use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Cache\RedisCache;
use App\Twitter\Domain\Membership\Exception\InvalidMemberException;
use App\Twitter\Domain\Publication\Exception\InvalidMemberAggregate;
use App\Twitter\Domain\Publication\PublishersListIdentity;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListDispatcherTrait;
use App\Twitter\Infrastructure\DependencyInjection\Subscription\MemberSubscriptionRepositoryTrait;
use App\Twitter\Infrastructure\Http\PaginationParams;
use App\Twitter\Infrastructure\Security\Authentication\AuthenticationTokenValidationTrait;
use App\Twitter\Infrastructure\Security\Cors\CorsHeadersAwareTrait;
use App\Membership\Domain\Entity\MemberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use function array_merge;
use function array_walk;
use function json_decode;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

class SubscriptionController
{
    use AuthenticationTokenValidationTrait;
    use CorsHeadersAwareTrait;
    use LoggerTrait;
    use MemberRepositoryTrait;
    use MemberSubscriptionRepositoryTrait;
    use PublishersListDispatcherTrait;

    public RedisCache $redisCache;

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
        $aggregateIdentity = PublishersListIdentity::fromRequest($request);

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

        $memberSubscriptions = $this->memberSubscriptionRepository
            ->getMemberSubscriptions($memberOrJsonResponse);

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
                    $this->publishersListDispatcher->dispatchMemberPublishersListMessage(
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

    private function willCacheResponse(Request $request): bool
    {
        $willCacheResponse = true;
        if (
            $request->headers->has('x-no-cache')
            && $request->headers->get('x-no-cache')
        ) {
            $willCacheResponse = !(bool) $request->headers->get('x-no-cache');
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
            if ($memberSubscriptions === null) {
                $memberSubscriptions = '';
            }
        }

        return $memberSubscriptions;
    }
}

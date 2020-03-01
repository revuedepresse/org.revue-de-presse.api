<?php
declare(strict_types=1);

namespace App\Twitter\Api;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Accessor\StatusAccessor;
use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Api\Moderator\ApiLimitModerator;
use App\Member\Entity\AggregateSubscription;
use App\Membership\Entity\MemberInterface;
use App\Membership\Exception\InvalidMemberIdentifier;
use App\Infrastructure\Repository\Membership\MemberRepository;
use App\Status\LikedStatusCollectionAwareInterface;
use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\OwnershipCollection;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\EmptyErrorCodeException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\InvalidTokensException;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\OverCapacityException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use App\Twitter\Exception\UnknownApiAccessException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Goutte\Client;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use TwitterOAuth;
use function array_key_exists;
use function is_array;
use function is_null;
use function is_numeric;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Accessor implements ApiAccessorInterface,
    TwitterErrorAwareInterface,
    LikedStatusCollectionAwareInterface
{
    public const ERROR_PROTECTED_ACCOUNT = 2048;

    private const MAX_RETRIES = 5;

    public StatusAccessor $statusAccessor;

    /**
     * @var string|Client
     */
    public $httpClient;

    public string $httpClientClass;

    public string $clientClass;

    public string $environment = 'dev';

    /**
     * @var LoggerInterface;
     */
    public LoggerInterface $twitterApiLogger;

    /**
     * @var string
     */
    public string $userToken;

    /**
     * @var bool
     */
    public bool $propagateNotFoundStatuses = false;

    /**
     * @var bool
     */
    public bool $shouldRaiseExceptionOnApiLimit = false;

    /**
     * @var string
     */
    protected string $apiHost = 'api.twitter.com';

    /**
     * @var LoggerInterface;
     */
    protected ?LoggerInterface $logger;

    /**
     * @var ApiLimitModerator $moderator
     */
    protected ApiLimitModerator $moderator;

    /**
     * @var string
     */
    protected string $userSecret;

    /**
     * @var string
     */
    protected ?string $consumerKey;

    /**
     * @var string
     */
    protected ?string $consumerSecret;

    /**
     * @var string
     */
    protected string $authenticationHeader;

    protected TokenRepositoryInterface $tokenRepository;

    protected TranslatorInterface $translator;

    protected bool $apiLimitReached = false;

    /**
     * @var MemberRepository
     */
    private MemberRepository $userRepository;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * @param array $members
     * @param int   $listId
     *
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws ReflectionException
     */
    public function addMembersToList(array $members, int $listId)
    {
        if (count($members) > 100) {
            throw new \LogicException('No more than 100 members can be added to a list at once');
        }

        $endpoint = $this->getAddMembersToListEndpoint() .
            "screen_name=" . implode(',', $members) .
            '&list_id=' . $listId;

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param TwitterOAuth $client
     * @param              $endpoint
     * @param array        $parameters
     *
     * @return stdClass|array
     */
    public function connectToEndpoint(
        TwitterOAuth $client,
        string $endpoint,
        array $parameters = []
    ) {
        if (
            strpos($endpoint, 'create.json') !== false
            || strpos($endpoint, 'create_all.json') !== false
            || strpos($endpoint, 'destroy.json') !== false
        ) {
            return $client->post($endpoint, $parameters);
        }

        $response = $client->get($endpoint, $parameters);

        return $response;
    }

    /**
     * @param string $endpoint
     *
     * @return stdClass|array
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function contactEndpoint(string $endpoint)
    {
        $response = null;

        $fetchContent = function ($endpoint) {
            try {
                return $this->fetchContent($endpoint);
            } catch (ConnectException | Exception $exception) {
                $this->logger->error($exception->getMessage(), $exception->getTrace());

                if ($exception instanceof ConnectException) {
                    throw $exception;
                }

                if (
                    $this->propagateNotFoundStatuses
                    && ($exception instanceof NotFoundStatusException)
                ) {
                    throw $exception;
                }

                return $this->convertExceptionIntoContent($exception);
            }
        };

        $content = $this->fetchContentWithRetries($endpoint, $fetchContent);

        if (!UnavailableResourceException::containErrors($content)) {
            return $content;
        }

        $loggedException = $this->logExceptionForToken($endpoint, $content);
        if ($this->matchWithOneOfTwitterErrorCodes($loggedException)) {
            return $this->handleTwitterErrorExceptionForToken($endpoint, $loggedException, $fetchContent);
        }

        return $this->delayUnknownExceptionHandlingOnEndpointForToken($endpoint);
    }

    /**
     * @param string $endpoint
     *
     * @return stdClass
     * @throws Exception
     */
    public function contactEndpointUsingBearerToken(string $endpoint)
    {
        $this->httpClient->setHeader('Authorization', $this->authenticationHeader);
        $this->httpClient->request('GET', $endpoint);

        /** @var Response $response */
        $response       = $this->httpClient->getResponse();
        $encodedContent = $response->getContent();

        return \Safe\json_decode($encodedContent);
    }

    /**
     * @param string $endpoint
     * @param Token  $token
     *
     * @return stdClass|array
     * @throws Exception
     */
    public function contactEndpointUsingConsumerKey(
        string $endpoint,
        Token $token
    ) {
        $tokens = $this->getTokens();

        $connection = $this->makeHttpClient($tokens);

        try {
            $content = $this->connectToEndpoint($connection, $endpoint);
            $this->checkApiLimit($connection);
        } catch (Exception $exception) {
            $content = $this->handleResponseContentWithEmptyErrorCode($exception, $token);
        }

        return $content;
    }

    /**
     * @param string     $endpoint
     * @param Token|null $token
     *
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function delayUnknownExceptionHandlingOnEndpointForToken(
        string $endpoint,
        Token $token = null
    ) {
        if ($this->shouldRaiseExceptionOnApiLimit) {
            throw new UnexpectedApiResponseException(
                sprintf('Could not access "%s" for an unknown reason.', $endpoint)
            );
        }

        $token = $this->maybeGetToken($endpoint, $token);

        /** Freeze token and wait for 15 minutes before getting back to operation */
        $this->tokenRepository->freezeToken($token->getOauthToken());
        $this->moderator->waitFor(
            15 * 60,
            ['{{ token }}' => $this->takeFirstTokenCharacters($token)]
        );

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param string $memberId
     *
     * @return MemberInterface
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws InvalidMemberIdentifier
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function ensureMemberHavingIdExists(string $memberId): MemberInterface
    {
        return $this->statusAccessor->ensureMemberHavingIdExists($memberId);
    }

    /**
     * @param string $memberName
     *
     * @return MemberInterface
     */
    public function ensureMemberHavingNameExists(string $memberName): MemberInterface
    {
        return $this->statusAccessor->ensureMemberHavingNameExists($memberName);
    }

    /**
     * @param stdClass $content
     *
     * @return UnavailableResourceException
     */
    public function extractContentErrorAsException(stdClass $content)
    {
        $message = $content->errors[0]->message;
        $code    = $content->errors[0]->code;

        return new UnavailableResourceException($message, $code);
    }

    /**
     * @param array $parameters
     *
     * @return stdClass|array
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function fetchLikes(array $parameters)
    {
        $endpoint = $this->getLikesEndpoint() . '&' . implode('&', $parameters);

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @return \API|mixed|object|stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function fetchRateLimitStatus()
    {
        $endpoint = $this->getRateLimitStatusEndpoint();

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param array $options
     *
     * @return stdClass|array
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function fetchStatuses(array $options): array
    {
        $parameters = $this->validateRequestOptions((object) $options);

        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            return $this->fetchLikes($parameters);
        }

        return $this->fetchTimelineStatuses($parameters);
    }

    /**
     * @param array $parameters
     *
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function fetchTimelineStatuses(array $parameters)
    {
        $endpoint = $this->getUserTimelineStatusesEndpoint() . '&' . implode('&', $parameters);

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @return int
     */
    public function getBadAuthenticationDataCode()
    {
        return self::ERROR_BAD_AUTHENTICATION_DATA;
    }

    /**
     * @return int
     */
    public function getEmptyReplyErrorCode()
    {
        return self::ERROR_EMPTY_REPLY;
    }

    /**
     * @return int
     */
    public function getExceededRateLimitErrorCode()
    {
        return self::ERROR_EXCEEDED_RATE_LIMIT;
    }

    /**
     * @param string $id
     *
     * @return MemberCollection
     * @throws ApiRateLimitingException
     * @throws InconsistentTokenRepository
     * @throws OptimisticLockException
     */
    public function getListMembers(string $id): MemberCollection
    {
        $listMembersEndpoint = $this->getListMembersEndpoint();
        $this->guardAgainstApiLimit($listMembersEndpoint);

        $sendRequest = function () use ($listMembersEndpoint, $id) {
            return $this->contactEndpoint(strtr($listMembersEndpoint, ['{{ id }}' => $id]));
        };

        $members = [];

        try {
            $members = $sendRequest();
        } catch (UnavailableResourceException $exception) {
            /**
             * @var Token $token
             */
            $token = $this->tokenRepository->findOneBy(['oauthToken' => $this->userToken]);
            $this->waitUntilTokenUnfrozen($token);

            $members = $sendRequest();
        } finally {
            return MemberCollection::fromArray($members->users);
        }
    }

    /**
     * @return int
     */
    public function getMemberNotFoundErrorCode()
    {
        return self::ERROR_NOT_FOUND;
    }

    public function getMemberOwnerships(
        string $screenName,
        int $cursor = -1,
        int $count = 800
    ): OwnershipCollection {
        $endpoint = $this->getUserOwnershipsEndpoint();
        $this->guardAgainstApiLimit($endpoint);

        $ownerships = $this->contactEndpoint(
            strtr(
                $endpoint,
                [
                    '{{ screenName }}' => $screenName,
                    '{{ reverse }}'    => true,
                    '{{ count }}'      => $count,
                    '{{ cursor }}'     => $cursor,
                ]
            )
        );

        return OwnershipCollection::fromArray($ownerships->lists);
    }

    /**
     * @return int
     */
    public function getProtectedAccountErrorCode()
    {
        return self::ERROR_PROTECTED_ACCOUNT;
    }

    /**
     * @return int
     */
    public function getSuspendedUserErrorCode()
    {
        return self::ERROR_SUSPENDED_USER;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getTwitterErrorCodes()
    {
        $reflection = new \ReflectionClass(__NAMESPACE__ . '\TwitterErrorAwareInterface');

        return $reflection->getConstants();
    }

    /**
     * @param $screenName
     *
     * @return \API|mixed|object|stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function getUserListSubscriptions($screenName)
    {
        return $this->contactEndpoint(
            strtr(
                $this->getMemberListSubscriptionsEndpoint(),
                ['{{ screenName }}' => $screenName]
            )
        );
    }

    /**
     * @param $screenName
     *
     * @return \API|mixed|object|stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function getUserLists($screenName)
    {
        return $this->contactEndpoint(
            strtr(
                $this->getUserListsEndpoint(),
                [
                    '{{ screenName }}' => $screenName,
                    '{{ reverse }}'    => true,
                ]
            )
        );
    }

    /**
     * @return int
     */
    public function getUserNotFoundErrorCode()
    {
        return self::ERROR_USER_NOT_FOUND;
    }

    /**
     * @param string $screenName
     * @param int    $cursor
     * @param int    $count
     *
     * @return OwnershipCollection
     */
    public function getUserOwnerships(
        string $screenName,
        int $cursor = -1,
        int $count = 800
    ): OwnershipCollection {
        return $this->getMemberOwnerships($screenName, $cursor, $count);
    }

    /**
     * @return mixed
     */
    public function getUserSecret()
    {
        return $this->userSecret;
    }

    /**
     * @param string $secret
     *
     * @return Accessor
     * @deprecated
     */
    public function setUserSecret(string $secret): self
    {
        $this->setOAuthSecret($secret);

        return $this;
    }

    private function setOAuthSecret(string $secret): self
    {
        $this->userSecret = $secret;

        return $this;
    }

    /**
     * @deprecated in favor of ->getOAuthToken()
     */
    public function getUserToken()
    {
        return $this->getOAuthToken();
    }

    public function getOAuthToken()
    {
        return $this->userToken;
    }

    /**
     * @param string $token
     *
     * @return Accessor
     * @deprecated in favor of ->setOAuthToken
     */
    public function setUserToken(string $token): self
    {
        return $this->setOAuthToken($token);
    }

    private function setOAuthToken(string $token): self
    {
        $this->userToken = $token;

        return $this;
    }

    /**
     * @param string $endpoint
     * @param bool   $findNextAvailableToken
     *
     * @return Token|null
     * @throws ApiRateLimitingException
     * @throws InconsistentTokenRepository
     * @throws OptimisticLockException
     */
    public function guardAgainstApiLimit(
        string $endpoint,
        bool $findNextAvailableToken = true
    ): ?Token {
        $apiLimitReached = $this->isApiLimitReached();
        $token           = null;

        $apiLimitReached = $apiLimitReached || $this->tokenRepository->isOauthTokenFrozen($this->userToken);
        if ($apiLimitReached) {
            $unfrozenToken = false;

            if ($findNextAvailableToken) {
                $token         = $this->tokenRepository->findFirstUnfrozenToken();
                $unfrozenToken = $token !== null;

                while ($apiLimitReached && $unfrozenToken) {
                    $apiLimitReached = !$this->isApiAvailableForToken($endpoint, $token);
                    $token           = $this->tokenRepository->findFirstUnfrozenToken();
                    $unfrozenToken   = $token !== null;
                }
            }

            if ($unfrozenToken) {
                return $token;
            }

            $message = $this->translator->trans('twitter.error.api_limit_reached.all_tokens', [], 'messages');
            $this->logger->info($message);

            if (!isset($token)) {
                $token = $this->tokenRepository->findFirstFrozenToken();
            }

            if ($token === null) {
                InconsistentTokenRepository::onEmptyAvailableTokenList();
            }

            $this->waitUntilTokenUnfrozen($token);

            return $token;
        }

        return $this->tokenRepository->findUnfrozenToken($this->userToken);
    }

    /**
     * @param array $options
     * @param bool  $shouldDiscoverFutureStatuses
     *
     * @return array
     */
    public function guessMaxId(array $options, bool $shouldDiscoverFutureStatuses)
    {
        if ($shouldDiscoverFutureStatuses) {
            $member = $this->userRepository->findOneBy(
                ['twitter_username' => $options['screen_name']]
            );
            if (($member instanceof MemberInterface) && !is_null($member->maxStatusId)) {
                $options['since_id'] = $member->maxStatusId + 1;

                return $options;
            }
        }

        if (array_key_exists('since_id', $options)) {
            $options['max_id'] = $options['since_id'] - 2;
            unset($options['since_id']);
        } else {
            $options['max_id'] = INF;
        }

        return $options;
    }

    /**
     * @param Exception $exception
     * @param Token     $token
     *
     * @return object
     * @throws Exception
     */
    public function handleResponseContentWithEmptyErrorCode(
        Exception $exception,
        Token $token
    ) {
        if ($exception->getCode() === 0) {
            $emptyErrorCodeMessage   = $this->translator->trans(
                'logs.info.empty_error_code',
                ['oauth token start' => $this->takeFirstTokenCharacters($token)],
                'logs'
            );
            $emptyErrorCodeException = EmptyErrorCodeException::encounteredWhenUsingToken(
                $emptyErrorCodeMessage,
                self::getExceededRateLimitErrorCode(),
                $exception
            );
            $this->logger->info($emptyErrorCodeException->getMessage());

            return $this->makeContentOutOfException($emptyErrorCodeException);
        }

        throw $exception;
    }

    /**
     * @param string                       $endpoint
     * @param UnavailableResourceException $exception
     * @param callable                     $fetchContent
     *
     * @return |null
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    public function handleTwitterErrorExceptionForToken(
        string $endpoint,
        UnavailableResourceException $exception,
        callable $fetchContent
    ) {
        if (
        !\in_array(
            $exception->getCode(),
            [self::ERROR_EXCEEDED_RATE_LIMIT, self::ERROR_OVER_CAPACITY],
            true
        )
        ) {
            $this->throwException($exception);
        }

        $token = $this->maybeGetToken($endpoint);
        $this->tokenRepository->freezeToken($token->getOauthToken());

        if (
            strpos($endpoint, '/statuses/user_timeline') === false
            && strpos($endpoint, '/favorites/list') === false
        ) {
            $this->throwException($exception);
        }

        $this->moderator->waitFor(
            15 * 60,
            ['{{ token }}' => $this->takeFirstTokenCharacters($token)]
        );

        return $this->fetchContentWithRetries($endpoint, $fetchContent);
    }

    /**
     * @param array $criteria
     *
     * @return bool
     */
    public function isAboutToCollectLikesFromCriteria(array $criteria): bool
    {
        if (!array_key_exists(self::INTENT_TO_FETCH_LIKES, $criteria)) {
            return false;
        }

        return $criteria[self::INTENT_TO_FETCH_LIKES];
    }

    public function isApiLimitReached()
    {
        return $this->apiLimitReached;
    }

    /**
     * @param string $endpoint
     *
     * @return bool
     * @throws Exception
     */
    public function isApiRateLimitReached($endpoint = '/statuses/show/:id')
    {
        $rateLimitStatus = $this->fetchRateLimitStatus();

        if (UnavailableResourceException::containErrors($rateLimitStatus)) {
            $message = $rateLimitStatus->errors[0]->message;

            $this->logger->error($message);
            throw new Exception($message, $rateLimitStatus->errors[0]->code);
        } else {
            $token = new Token();
            $token->setOauthToken($this->userToken);

            $fullEndpoint = $endpoint;
            $resourceType = 'statuses';

            if (!isset($rateLimitStatus->resources->$resourceType)) {
                return false;
            }

            $availableEndpoints = get_object_vars($rateLimitStatus->resources->$resourceType);
            if (!array_key_exists($endpoint, $availableEndpoints)) {
                $endpoint = null;

                if (false !== strpos($fullEndpoint, '/users/show')) {
                    $endpoint     = '/users/show/:id';
                    $resourceType = 'users';
                }

                if (false !== strpos($fullEndpoint, '/statuses/user_timeline')) {
                    $endpoint = '/statuses/user_timeline';
                }

                if (false !== strpos($fullEndpoint, '/lists/ownerships')) {
                    $endpoint     = '/lists/ownerships';
                    $resourceType = 'lists';
                }

                if (false !== strpos($fullEndpoint, '/favorites/list')) {
                    $endpoint     = '/favorites/list';
                    $resourceType = 'favorites';
                }

                if (false !== strpos($fullEndpoint, '/friends/ids')) {
                    $endpoint     = '/friends/ids';
                    $resourceType = 'friends';
                }

                if (false !== strpos($fullEndpoint, '/followers/ids')) {
                    $endpoint     = '/followers/ids';
                    $resourceType = 'followers';
                }

                if (false !== strpos($fullEndpoint, '/friendships/create')) {
                    $endpoint     = '/friendships/create';
                    $resourceType = 'friendships';
                }
            }

            $this->logger->info(json_encode($rateLimitStatus));

            if (!is_null($endpoint) && isset($rateLimitStatus->resources->$resourceType)) {
                $limit = $rateLimitStatus->resources->$resourceType->$endpoint->limit;

                if (is_null($limit)) {
                    return false;
                }

                $remainingCalls = $rateLimitStatus->resources->$resourceType->$endpoint->remaining;

                $remainingCallsMessage = $this->translator->trans(
                    'logs.info.calls_remaining',
                    [
                        'count'           => $remainingCalls,
                        'remaining_calls' => $remainingCalls,
                        'endpoint'        => $endpoint,
                        'identifier'      => $this->takeFirstTokenCharacters($token),
                    ],
                    'logs'
                );
                $this->logger->info($remainingCallsMessage);

                return $this->lessRemainingCallsThanTenPercentOfLimit($remainingCalls, $limit);
            } else {
                $this->logger->info(sprintf('Could not compute remaining calls for "%s"', $fullEndpoint));

                return false;
            }
        }
    }

    /**
     * Returns true if there are more remaining calls than 10 % of the limit
     *
     * @param $remainingCalls
     * @param $limit
     *
     * @return bool
     */
    public function lessRemainingCallsThanTenPercentOfLimit($remainingCalls, $limit)
    {
        return $remainingCalls < floor($limit * 1 / 10);
    }

    /**
     * @param string     $endpoint
     * @param stdClass   $content
     * @param Token|null $token
     *
     * @return UnavailableResourceException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function logExceptionForToken(string $endpoint, stdClass $content, Token $token = null)
    {
        $exception = $this->extractContentErrorAsException($content);

        $this->twitterApiLogger->info('[message] ' . $exception->getMessage());
        $this->twitterApiLogger->info('[code] ' . $exception->getCode());

        $token = $this->maybeGetToken($endpoint, $token);
        $this->twitterApiLogger->info('[token] ' . $token->getOauthToken());

        return $exception;
    }

    /**
     * @param UnavailableResourceException $exception
     *
     * @return object
     */
    public function makeContentOutOfException(UnavailableResourceException $exception)
    {
        return (object) [
            'errors' => [
                (object) [
                    'message' => $exception->getMessage(),
                    'code'    => self::getExceededRateLimitErrorCode(),
                ],
            ],
        ];
    }

    /**
     * @param array $tokens
     *
     * @return mixed
     */
    public function makeHttpClient(array $tokens)
    {
        $httpClient = $this->httpClient;

        return new $httpClient(
            $tokens['key'],
            $tokens['secret'],
            $tokens['oauth'],
            $tokens['oauth_secret']
        );
    }

    /**
     * @param UnavailableResourceException $exception
     *
     * @return bool
     * @throws ReflectionException
     */
    public function matchWithOneOfTwitterErrorCodes(UnavailableResourceException $exception)
    {
        return in_array($exception->getCode(), $this->getTwitterErrorCodes());
    }

    /**
     * @param string $endpoint
     *
     * @return Token|null
     * @throws ApiRateLimitingException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function preEndpointContact(string $endpoint): ?Token
    {
        $tokens = $this->getTokens();

        /** @var Token $token */
        $token = $this->tokenRepository->refreshFreezeCondition(
            $tokens['oauth'],
            $this->logger
        );

        if (!$token->isFrozen()) {
            return $token;
        }

        return $this->guardAgainstApiLimit($endpoint);
    }

    /**
     * @param string $query
     *
     * @return stdClass|array
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function saveSearch(string $query)
    {
        $endpoint = $this->getCreateSavedSearchEndpoint() . "query=$query";

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param string $query
     * @param string $params
     *
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function search(string $query, string $params = ''): stdClass
    {
        $endpoint = $this->getSearchEndpoint() . "q=$query&count=100" . $params;

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param TokenInterface $token
     */
    public function setAccessToken(TokenInterface $token)
    {
        $this->setOAuthToken($token->getOAuthToken());
        $this->setOAuthSecret($token->getOAuthSecret());

        if ($token->hasConsumerKey()) {
            $this->setConsumerKey($token->getConsumerKey());
            $this->setConsumerSecret($token->getConsumerKey());
        }
    }

    /**
     * @param string $host
     *
     * @return $this
     */
    public function setApiHost($host)
    {
        $this->apiHost = $host;

        return $this;
    }

    /**
     * @param string $header
     *
     * @return $this
     */
    public function setAuthenticationHeader($header)
    {
        $this->authenticationHeader = $header;

        return $this;
    }

    /**
     * @param string $clientClass
     *
     * @return $this
     */
    public function setClientClass($clientClass)
    {
        $this->clientClass = $clientClass;

        return $this;
    }

    /**
     * @param string $consumerKey
     *
     * @return $this
     */
    public function setConsumerKey(string $consumerKey): self
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    /**
     * @param string $consumerSecret
     *
     * @return $this
     */
    public function setConsumerSecret(string $consumerSecret): self
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    /**
     * @param string $httpClientClass
     *
     * @return $this
     */
    public function setHttpClientClass(string $httpClientClass)
    {
        $this->httpClientClass = $httpClientClass;

        return $this;
    }

    /**
     * @param LoggerInterface|null $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger = null): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function setMemberRepository(MemberRepository $memberRepository): self
    {
        $this->userRepository = $memberRepository;

        return $this;
    }

    public function setModerator(ApiLimitModerator $moderator = null): self
    {
        $this->moderator = $moderator;

        return $this;
    }

    /**
     * @param TokenRepositoryInterface $tokenRepository
     *
     * @return $this
     */
    public function setTokenRepository(TokenRepositoryInterface $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

    /**
     * @param Translator $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    public function setupClient()
    {
        $requesterClass   = $this->clientClass;
        $this->httpClient = new $requesterClass();

        $httpClientClass = $this->httpClientClass;
        $httpClient      = new $httpClientClass();
        $this->httpClient->setClient($httpClient);
    }

    /**
     * @param string $screenName
     *
     * @return bool
     */
    public function shouldSkipCollectForMemberWithScreenName(string $screenName)
    {
        $member = $this->userRepository->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof MemberInterface) {
            return false;
        }

        return $member->isProtected()
            || $member->hasBeenDeclaredAsNotFound()
            || $member->isSuspended()
            || $member->isAWhisperer();
    }

    /**
     * @return bool
     */
    public function shouldUseBearerToken(): bool
    {
        if (!isset($this->authenticationHeader)) {
            return false;
        }

        return $this->authenticationHeader !== null;
    }

    /**
     * @param string $screenName
     * @param int    $cursor
     *
     * @return \API|mixed|object|stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws OptimisticLockException
     */
    public function showMemberSubscribees(string $screenName, int $cursor = -1)
    {
        $showUserFriendEndpoint = $this->getShowMemberSubscribeesEndpoint();

        try {
            return $this->contactEndpoint(
                str_replace(
                    '{{ screen_name }}',
                    $screenName,
                    $showUserFriendEndpoint
                ) . '&cursor=' . $cursor
            );
        } catch (SuspendedAccountException  $exception) {
            $this->userRepository->declareMemberAsSuspended($screenName);
        } catch (NotFoundMemberException $exception) {
            $this->userRepository->declareUserAsNotFoundByUsername($screenName);
        } catch (ProtectedAccountException $exception) {
            $this->userRepository->declareUserAsProtected($screenName);
        } finally {
            if (isset($exception)) {
                return (object) ['ids' => []];
            }
        }
    }

    /**
     * @param $identifier
     *
     * @return \API|mixed|object|stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function showStatus($identifier)
    {
        if (!is_numeric($identifier)) {
            throw new \InvalidArgumentException('A status identifier should be an integer');
        }
        $showStatusEndpoint = $this->getShowStatusEndpoint($version = '1.1');

        try {
            return $this->contactEndpoint(strtr($showStatusEndpoint, ['{{ id }}' => $identifier]));
        } catch (NotFoundStatusException $exception) {
            $this->statusAccessor->declareStatusNotFoundByIdentifier($identifier);

            throw $exception;
        }
    }

    /**
     * @param $identifier
     *
     * @return array|stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     */
    public function getMemberProfile(string $identifier): stdClass
    {
        $screenName = null;
        $userId     = null;

        if (is_numeric($identifier)) {
            $userId = $identifier;
            $option = 'user_id';

            $this->guardAgainstSpecialMemberWithIdentifier($identifier);
        } else {
            $screenName = $identifier;
            $option     = 'screen_name';

            $this->guardAgainstSpecialMembers($screenName);
        }

        $showUserEndpoint = $this->getShowUserEndpoint($version = '1.1', $option);
        $this->guardAgainstApiLimit($showUserEndpoint);

        try {
            $twitterUser = $this->contactEndpoint(
                strtr(
                    $showUserEndpoint,
                    [
                        '{{ screen_name }}' => $screenName,
                        '{{ user_id }}'     => $userId
                    ]
                )
            );
        } catch (UnavailableResourceException $exception) {
            if ($exception->getCode() === self::ERROR_SUSPENDED_USER) {
                $suspendedMember = $this->userRepository->suspendMemberByScreenNameOrIdentifier($identifier);
                $this->logSuspendedMemberMessage($suspendedMember->getTwitterUsername());

                SuspendedAccountException::raiseExceptionAboutSuspendedMemberHavingScreenName(
                    $suspendedMember->getTwitterUsername(),
                    $exception->getCode(),
                    $exception
                );
            }

            if (
                $exception->getCode() === self::ERROR_NOT_FOUND
                || $exception->getCode() === self::ERROR_USER_NOT_FOUND
            ) {
                $member = $this->userRepository->findOneBy(['twitter_username' => $screenName]);
                if (!($member instanceof MemberInterface) && $screenName !== null) {
                    $member = $this->userRepository->declareMemberHavingScreenNameNotFound($screenName);
                }

                if ($member instanceof MemberInterface && !$member->isNotFound()) {
                    $this->userRepository->declareMemberAsNotFound($member);
                }

                $this->logNotFoundMemberMessage($screenName ?? $identifier);
                NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName(
                    is_null($screenName) ? $identifier : $screenName,
                    $exception->getCode(),
                    $exception
                );
            }

            throw $exception;
        }

        $this->guardAgainstUnavailableResource($twitterUser);

        return $twitterUser;
    }

    /**
     * @param $identifier
     *
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws InvalidMemberIdentifier
     * @throws ORMException
     */
    public function showUser(string $identifier): stdClass
    {
       return $this->getMemberProfile($identifier);
    }

    /**
     * @param string $screenName
     *
     * @return array|object|stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function showUserFriends(string $screenName)
    {
        $showUserFriendEndpoint = $this->getShowUserFriendsEndpoint();

        try {
            return $this->contactEndpoint(str_replace('{{ screen_name }}', $screenName, $showUserFriendEndpoint));
        } catch (SuspendedAccountException  $exception) {
            $this->userRepository->declareMemberAsSuspended($screenName);
        } catch (NotFoundMemberException $exception) {
            $this->userRepository->declareUserAsNotFoundByUsername($screenName);
        } catch (ProtectedAccountException $exception) {
            $this->userRepository->declareUserAsProtected($screenName);
        } finally {
            if (isset($exception)) {
                return (object) ['ids' => []];
            }
        }
    }

    /**
     * @param AggregateSubscription $subscription
     *
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws ReflectionException
     */
    public function subscribeToMemberTimeline(AggregateSubscription $subscription)
    {
        $endpoint = $this->getCreateFriendshipsEndpoint();

        return $this->contactEndpoint(
            str_replace(
                '{{ screen_name }}',
                $subscription->subscription->getTwitterUsername(),
                $endpoint
            )
        );
    }

    /**
     * @param $connection
     */
    protected function checkApiLimit(\TwitterOAuth $connection)
    {
        if (($connection->http_info['http_code'] == 404) && $this->propagateNotFoundStatuses) {
            $message = sprintf(
                'A status has been removed (%s)',
                $connection->http_info['url']
            );
            $this->twitterApiLogger->info($message);
            throw new NotFoundStatusException($message, self::ERROR_NOT_FOUND);
        }

        $this->twitterApiLogger->info(
            sprintf(
                '[HTTP code] %s',
                print_r($connection->http_info['http_code'], true)
            )
        );
        $this->twitterApiLogger->info(
            sprintf(
                '[HTTP URL] %s',
                $connection->http_info['url']
            )
        );
        if (isset($connection->http_header)) {
            if (array_key_exists('x_rate_limit_limit', $connection->http_header)) {
                $limit          = (int) $connection->http_header['x_rate_limit_limit'];
                $remainingCalls = (int) $connection->http_header['x_rate_limit_remaining'];

                $this->twitterApiLogger->info(
                    sprintf(
                        '[X-Rate-Limit-Remaining] %s',
                        $connection->http_header['x_rate_limit_remaining']
                    )
                );

                $this->apiLimitReached = $this->lessRemainingCallsThanTenPercentOfLimit($remainingCalls, $limit);
            }
        }
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getAddMembersToListEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/members/create_all.json' .
            '?';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getApiBaseUrl($version = '1.1')
    {
        return 'https://' . $this->apiHost . '/' . $version;
    }

    /**
     * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-create
     *
     * @param string $version
     *
     * @return string
     */
    protected function getCreateFriendshipsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/friendships/create.json?screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getCreateSavedSearchEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/saved_searches/create.json?';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getDestroyFriendshipsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/friendships/destroy.json?screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getLikesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/favorites/list.json?' .
            'tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0';
    }

    /**
     * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-members
     *
     * @param string $version
     *
     * @return string
     */
    protected function getListMembersEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/members.json?count=5000&list_id={{ id }}';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getRateLimitStatusEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/application/rate_limit_status.json?' .
            'resources=favorites,statuses,users,lists,friends,friendships,followers';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getSearchEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/search/tweets.json?tweet_mode=extended&';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getShowMemberSubscribeesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/followers/ids.json?count=5000&screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getShowStatusEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/show.json?id={{ id }}&tweet_mode=extended';
    }

    /**
     * @param string $version
     * @param string $option
     *
     * @return string
     */
    protected function getShowUserEndpoint($version = '1.1', $option = 'screen_name')
    {
        if ($option === 'screen_name') {
            $parameters = 'screen_name={{ screen_name }}';
        } else {
            $parameters = 'user_id={{ user_id }}';
        }

        return $this->getApiBaseUrl($version) . '/users/show.json?' . $parameters;
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getShowUserFriendsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/friends/ids.json?count=5000&screen_name={{ screen_name }}';
    }

    /**
     * @return array
     */
    protected function getTokens(): array
    {
        try {
            if ($this->userSecret === null || $this->userToken === null) {
                InvalidTokensException::throws();
            }
        } catch (InvalidTokensException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return [
            'oauth'        => $this->userToken,
            'oauth_secret' => $this->userSecret,
            'key'          => $this->consumerKey,
            'secret'       => $this->consumerSecret,
        ];
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getUserListsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/list.json?reverse={{ reverse }}&screen_name={{ screenName }}';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getUserOwnershipsEndpoint(string $version = '1.1'): string
    {
        return $this->getApiBaseUrl($version) . '/lists/ownerships.json?reverse={{ reverse }}' .
            '&screen_name={{ screenName }}' .
            '&count={{ count }}&cursor={{ cursor }}';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getUserTimelineStatusesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/user_timeline.json?' .
            'tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0';
    }

    /**
     * @param $twitterUser
     *
     * @return bool
     * @throws ProtectedAccountException
     * @throws OptimisticLockException
     */
    protected function guardAgainstProtectedAccount($twitterUser)
    {
        if (!isset($twitterUser->protected) || !$twitterUser->protected) {
            return true;
        }

        $this->userRepository->declareUserAsProtected($twitterUser->screen_name);

        ProtectedAccountException::raiseExceptionAboutProtectedMemberHavingScreenName(
            $twitterUser->screen_name,
            self::ERROR_PROTECTED_ACCOUNT
        );
    }

    /**
     * @param $twitterUser
     *
     * @return bool
     * @throws ProtectedAccountException
     * @throws UnavailableResourceException
     * @throws OptimisticLockException
     */
    protected function guardAgainstUnavailableResource($twitterUser)
    {
        $validUser = is_object($twitterUser);

        if ($validUser && !isset($twitterUser->errors)) {
            return $this->guardAgainstProtectedAccount($twitterUser);
        }

        if ($validUser && isset($twitterUser->errors)) {
            $errorCode    = $twitterUser->errors[0]->code;
            $errorMessage = $twitterUser->errors[0]->message;
            $this->logger->error($errorMessage);

            $this->throwUnavailableResourceException($errorMessage, $errorCode);
        }

        $errorMessage = 'Unavailable user';
        $this->logger->info($errorMessage);

        $this->throwUnavailableResourceException($errorMessage, 0);
    }

    /**
     * @param $endpoint
     *
     * @return bool
     * @throws OptimisticLockException
     */
    protected function isApiAvailable($endpoint)
    {
        $availableApi = false;

        try {
            if (!$this->isApiRateLimitReached($endpoint)) {
                $availableApi = true;
            }
        } catch (Exception $exception) {
            switch ($exception->getCode()) {
                case $this->getBadAuthenticationDataCode():

                    $this->logger->error(
                        sprintf(
                            'Please check your token consumer key (%s)',
                            $exception->getMessage()
                        )
                    );

                    $availableApi = false;

                    break;

                case $this->getEmptyReplyErrorCode():

                    $availableApi = true;

                    break;

                default:
                    $this->logger->error($exception->getMessage());
                    $this->tokenRepository->freezeToken($this->userToken);
            }
        }

        return $availableApi;
    }

    /**
     * @param       $endpoint
     * @param Token $token
     *
     * @return bool
     * @throws OptimisticLockException
     */
    protected function isApiAvailableForToken($endpoint, Token $token)
    {
        $this->setUserToken($token->getOauthToken());
        $this->setUserSecret($token->getOauthTokenSecret());
        $this->setConsumerKey($token->consumerKey);
        $this->setConsumerSecret($token->consumerSecret);

        return $this->isApiAvailable($endpoint);
    }

    /**
     * @param Token $token
     *
     * @return string
     */
    protected function takeFirstTokenCharacters(Token $token)
    {
        return substr($token->getOauthToken(), 0, 8);
    }

    /**
     * @param UnavailableResourceException $exception
     *
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    protected function throwException(UnavailableResourceException $exception)
    {
        if ($exception->getCode() === self::ERROR_SUSPENDED_USER) {
            throw new SuspendedAccountException($exception->getMessage(), $exception->getCode());
        }

        if ($exception->getCode() === self::ERROR_USER_NOT_FOUND) {
            throw new NotFoundMemberException($exception->getMessage(), $exception->getCode());
        }

        throw $exception;
    }

    /**
     * @param $errorMessage
     * @param $errorCode
     *
     * @throws UnavailableResourceException
     */
    protected function throwUnavailableResourceException($errorMessage, $errorCode)
    {
        throw new UnavailableResourceException($errorMessage, $errorCode);
    }

    /**
     * @param $options
     *
     * @return array
     */
    protected function validateRequestOptions(\stdClass $options): array
    {
        $validatedOptions = [];

        if (isset($options->{'count'})) {
            $resultsCount = $options->{'count'};
        } else {
            $resultsCount = 1;
        }
        $validatedOptions[] = 'count' . '=' . $resultsCount;

        if (isset($options->{'max_id'})) {
            $maxId              = $options->{'max_id'};
            $validatedOptions[] = 'max_id' . '=' . $maxId;
        }

        if (isset($options->{'screen_name'})) {
            $screenName         = $options->{'screen_name'};
            $validatedOptions[] = 'screen_name' . '=' . $screenName;
        }

        if (isset($options->{'since_id'})) {
            $sinceId            = $options->{'since_id'};
            $validatedOptions[] = 'since_id' . '=' . $sinceId;
        }

        return $validatedOptions;
    }

    /**
     * @param Token $token
     *
     * @throws ApiRateLimitingException
     */
    protected function waitUntilTokenUnfrozen(Token $token)
    {
        if ($this->shouldRaiseExceptionOnApiLimit) {
            throw new ApiRateLimitingException('Impossible to access the source API at the moment');
        }

        $now = new \DateTime;
        $this->moderator->waitFor(
            $token->getFrozenUntil()->getTimestamp() - $now->getTimestamp(),
            ['{{ token }}' => $this->takeFirstTokenCharacters($token)]
        );
    }

    /**
     * @param $exception
     *
     * @return stdClass
     */
    private function convertExceptionIntoContent($exception)
    {
        return (object) [
            'errors' => [
                (object) [
                    'message' => $exception->getMessage(),
                    'code'    => $exception->getCode(),
                ],
            ],
        ];
    }

    /**
     * @param string $endpoint
     *
     * @return array|stdClass
     * @throws ApiRateLimitingException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    private function fetchContent(string $endpoint)
    {
        if ($this->shouldUseBearerToken()) {
            $this->setupClient();

            return $this->contactEndpointUsingBearerToken($endpoint);
        }

        $token = $this->preEndpointContact($endpoint);

        return $this->contactEndpointUsingConsumerKey($endpoint, $token);
    }

    /**
     * @param string   $endpoint
     * @param callable $fetchContent
     *
     * @return stdClass|array
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws SuspendedAccountException
     * @throws UnknownApiAccessException
     */
    private function fetchContentWithRetries(
        string $endpoint,
        callable $fetchContent
    ) {
        $content = null;

        $this->logger->info(
            sprintf(
                'About to fetch content by making contact with endpoint "%s"',
                $endpoint
            )
        );

        $retries = 0;
        while ($retries < self::MAX_RETRIES + 1) {
            try {
                $content = $fetchContent($endpoint);
                UnavailableResourceException::guardAgainstContentFetchingException(
                    $content,
                    $endpoint,
                    function (string $endpoint) {
                        return $this->delayUnknownExceptionHandlingOnEndpointForToken(
                            $endpoint
                        );
                    }
                );

                break;
            } catch (OverCapacityException $exception) {
                $this->logger->info(
                    sprintf(
                        'About to retry making contact with endpoint (retry #%d out of %d) "%s"',
                        $retries + 1,
                        self::MAX_RETRIES,
                        $endpoint
                    )
                );

                $retries++;
            }
        }

        return $content;
    }

    /**
     * @param string $version
     *
     * @return string
     */
    private function getMemberListSubscriptionsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/subscriptions.json?count=1000&screen_name={{ screenName }}';
    }

    /**
     * @param string $identifier
     *
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     */
    private function guardAgainstSpecialMemberWithIdentifier(string $identifier): void
    {
        $member = $this->userRepository->findOneBy(['twitterID' => $identifier]);
        if ($member instanceof MemberInterface) {
            if ($member->isSuspended()) {
                $this->logSuspendedMemberMessage($member->getTwitterUsername());
                SuspendedAccountException::raiseExceptionAboutSuspendedMemberHavingScreenName(
                    $member->getTwitterUsername(),
                    self::ERROR_SUSPENDED_ACCOUNT
                );
            }

            if ($member->isNotFound()) {
                $this->logNotFoundMemberMessage($member->getTwitterUsername());
                NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName(
                    $member->getTwitterUsername(),
                    self::ERROR_NOT_FOUND
                );
            }

            if ($member->isProtected()) {
                $this->logProtectedMemberMessage($member->getTwitterUsername());
                ProtectedAccountException::raiseExceptionAboutProtectedMemberHavingScreenName(
                    $member->getTwitterUsername(),
                    self::ERROR_PROTECTED_ACCOUNT
                );
            }
        }
    }

    /**
     * @param $screenName
     *
     * @throws NotFoundMemberException
     * @throws SuspendedAccountException
     */
    private function guardAgainstSpecialMembers($screenName): void
    {
        $member = $this->userRepository->findOneBy(['twitter_username' => $screenName]);
        if ($member instanceof MemberInterface) {
            if ($member->isSuspended()) {
                $this->logSuspendedMemberMessage($screenName);
                SuspendedAccountException::raiseExceptionAboutSuspendedMemberHavingScreenName(
                    $screenName,
                    self::ERROR_SUSPENDED_ACCOUNT
                );
            }

            if ($member->isNotFound()) {
                $this->logNotFoundMemberMessage($screenName);
                NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName(
                    $screenName,
                    self::ERROR_NOT_FOUND
                );
            }

            if ($member->isProtected()) {
                $this->logProtectedMemberMessage($screenName);
                ProtectedAccountException::raiseExceptionAboutProtectedMemberHavingScreenName(
                    $screenName,
                    self::ERROR_PROTECTED_ACCOUNT
                );
            }
        }
    }

    /**
     * @param $screenName
     *
     * @return string
     */
    private function logNotFoundMemberMessage($screenName): string
    {
        $notFoundMemberMessage = $this->translator->trans(
            'amqp.output.not_found_member',
            ['{{ user }}' => $screenName],
            'messages'
        );
        $this->logger->info($notFoundMemberMessage);

        return $notFoundMemberMessage;
    }

    /**
     * @param $screenName
     *
     * @return string
     */
    private function logProtectedMemberMessage($screenName): string
    {
        $protectedMemberMessage = $this->translator->trans(
            'amqp.output.protected_member',
            ['{{ user }}' => $screenName],
            'messages'
        );
        $this->logger->info($protectedMemberMessage);

        return $protectedMemberMessage;
    }

    /**
     * @param $screenName
     *
     * @return string
     */
    private function logSuspendedMemberMessage($screenName): string
    {
        $suspendedMessageMessage = $this->translator->trans(
            'amqp.output.suspended_account',
            ['{{ user }}' => $screenName],
            'messages'
        );
        $this->logger->info($suspendedMessageMessage);

        return $suspendedMessageMessage;
    }

    /**
     * @param string     $endpoint
     * @param Token|null $token
     *
     * @return Token
     * @throws ApiRateLimitingException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function maybeGetToken(string $endpoint, Token $token = null): Token
    {
        if ($token !== null) {
            return $token;
        }

        return $this->preEndpointContact($endpoint);
    }
}

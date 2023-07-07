<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

use Abraham\TwitterOAuth\TwitterOAuth as BaseTwitterApiClient;
use Abraham\TwitterOAuth\TwitterOAuthException;
use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\Entity\MemberInList;
use App\Membership\Infrastructure\Repository\Exception\InvalidMemberIdentifier;
use App\Membership\Infrastructure\Repository\MemberRepository;
use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException;
use App\Twitter\Domain\Http\Client\Fallback\TwitterHttpApiClientInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Http\Resource\MemberCollectionInterface;
use App\Twitter\Domain\Http\TwitterAPIAwareInterface;
use App\Twitter\Infrastructure\DependencyInjection\Http\TwitterHttpApiAwareTrait;
use App\Twitter\Infrastructure\Exception\BadAuthenticationDataException;
use App\Twitter\Infrastructure\Exception\BlockedFromViewingMemberProfileException;
use App\Twitter\Infrastructure\Exception\EmptyErrorCodeException;
use App\Twitter\Infrastructure\Exception\InconsistentTokenRepository;
use App\Twitter\Infrastructure\Exception\InvalidTokensException;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\OverCapacityException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Exception\UnknownApiAccessException;
use App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException;
use App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException;
use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException;
use App\Twitter\Infrastructure\Http\Client\Fallback\FallbackToken;
use App\Twitter\Infrastructure\Http\Compliance\RateLimitCompliance;
use App\Twitter\Infrastructure\Http\Entity\FreezableToken;
use App\Twitter\Infrastructure\Http\Entity\NullToken;
use App\Twitter\Infrastructure\Http\Exception\UnavailableTokenException;
use App\Twitter\Infrastructure\Http\Resource\MemberCollection;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Selector\ListsBatchSelector;
use App\Twitter\Infrastructure\Translation\Translator;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use stdClass;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_key_exists;
use function is_null;
use function is_numeric;
use function strtr;
use const PHP_URL_HOST;
use const PHP_URL_PASS;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;
use const PHP_URL_USER;

class HttpClient implements
    HttpClientInterface,
    HttpSearchParamReducerInterface
{
    use HttpSearchParamReducerTrait;
    use TwitterHttpApiAwareTrait;

    private const MAX_RETRIES = 5;

    public string $environment = 'dev';

    public string $userToken;

    public bool $propagateNotFoundStatuses = false;

    public bool $shouldRaiseExceptionOnApiLimit = false;

    public TweetAwareHttpClient $tweetAwareHttpClient;

    public LoggerInterface $twitterApiLogger;

    protected ?LoggerInterface $logger;

    protected RateLimitCompliance $moderator;

    protected string $userSecret;

    protected ?string $consumerKey;

    protected ?string $consumerSecret;

    protected TokenRepositoryInterface $tokenRepository;

    protected TranslatorInterface $translator;

    protected bool $apiLimitReached = false;

    private MemberRepositoryInterface $memberRepository;

    private BaseTwitterApiClient $twitterClient;

    public function __construct(
        string $consumerKey,
        string $consumerSecret,
        string $accessTokenKey,
        string $accessTokenSecret,
        TokenRepositoryInterface $tokenRepository,
        LoggerInterface $logger = null
    ) {
        $this->setUpTwitterClient(
            $consumerKey,
            $consumerSecret,
            $accessTokenKey,
            $accessTokenSecret
        );

        $this->tokenRepository = $tokenRepository;

        $this->setLogger($logger);
    }

    /**
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     * @throws \Exception
     */
    public function connectToEndpoint(
        string $endpoint,
        array $parameters = []
    ): object|array
    {
        $this->logger->info("[ accessing Twitter API endpoint: {$endpoint} ]");

        $intendingToToAddMemberToList = $this->intendingToAddMemberToList($endpoint);

        $version = $this->whichTwitterAPIVersionToCall($intendingToToAddMemberToList);
        if ($version === self::TWITTER_API_VERSION_2) {
            $this->twitterClient->setApiVersion(self::TWITTER_API_VERSION_2);
        }

        $this->twitterApiLogger->info('About to call Twitter API', ['version' => $version]);

        $path = $this->reducePath($endpoint, $version);
        $parameters = $this->reduceParameters($endpoint, $parameters);

        $httpMethod = $this->whichHttpMethod($intendingToToAddMemberToList, $endpoint);

        if ($httpMethod === self::HTTP_METHOD_POST) {
            $response = $this->twitterClient->post($path, $parameters, json: $intendingToToAddMemberToList);

            if ($intendingToToAddMemberToList) {
                $this->twitterApiLogger->info('sent POST request to "lists/members/create_all" route',
                    ['params' => $parameters, 'path' => $path, 'response' => $response]
                );
            }

            return $response;
        }

        $response = $this->twitterClient->get($path, $parameters);

        $endpointContainsListsMembers = str_contains($endpoint, 'lists/members.json');
        if ($endpointContainsListsMembers) {
            $this->twitterApiLogger->info('sent GET request to "lists/members" route',
                ['params' => $parameters, 'path' => $path, 'response' => $response]
            );
        }

        return $response;
    }

    /**
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws UnknownApiAccessException
     * @throws BlockedFromViewingMemberProfileException
     * @throws FallbackHttpAccessException
     */
    public function contactEndpoint(string $endpoint): array|stdClass|null
    {
        $fetchContent = function ($endpoint) {
            try {
                return $this->fetchContent($endpoint);
            } catch (ConnectionException $exception) {
                $this->logger->info(
                    'Reconnecting after having lost connection',
                    ['message' => $exception->getMessage()]
                );
                $this->tokenRepository->reconnect();

                return $this->fetchContent($endpoint);
            } catch (ConnectException | Exception $exception) {
                $this->logger->error($exception->getMessage(), $exception->getTrace());

                if ($exception instanceof ConnectException) {
                    throw $exception;
                }

                if (
                    $this->propagateNotFoundStatuses
                    && ($exception instanceof TweetNotFoundException)
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

    private TwitterHttpApiClientInterface $fallbackHttpClient;

    public function setFallbackTwitterHttpClient(TwitterHttpApiClientInterface $fallbackHttpClient)
    {
        $this->fallbackHttpClient = $fallbackHttpClient;
    }

    /**
     * @throws Exception
     */
    public function contactEndpointUsingConsumerKey(
        string $endpoint,
        TokenInterface $token
    ): object|array
    {
        $this->setUpTwitterClient(
            $token->getConsumerKey(),
            $token->getConsumerSecret(),
            $token->getAccessToken(),
            $token->getAccessTokenSecret(),
        );

        try {
            $content = $this->connectToEndpoint($endpoint);

            $this->checkApiLimit();
        } catch (Exception $exception) {
            // Retry in case of operation timed out error raised by curl
            if ($exception instanceof TwitterOAuthException &&
                $exception->getCode() === CURLE_OPERATION_TIMEDOUT
            ) {
                $this->logger->info('Retrying to reach endpoint after operation timed out');

                return $this->contactEndpointUsingConsumerKey($endpoint, $token);
            }

            $content = $this->handleResponseContentWithEmptyErrorCode($exception, $token);
        }

        return $content;
    }

    /**
     * @throws FallbackHttpAccessException
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws BlockedFromViewingMemberProfileException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \App\Twitter\Infrastructure\Exception\UnknownApiAccessException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     */
    public function delayUnknownExceptionHandlingOnEndpointForToken(
        string $endpoint,
        TokenInterface $token = null
    ): array|stdClass|null
    {
        if ($this->shouldRaiseExceptionOnApiLimit) {
            throw new UnexpectedApiResponseException(
                sprintf('Could not access "%s" for an unknown reason.', $endpoint)
            );
        }

        $token = $this->maybeGetToken($endpoint, $token);

        try {
            /**
             * Freeze token and wait for 15 minutes,
             * before getting back to operation
             */
            $this->tokenRepository->freezeToken($token);
            $this->moderator->waitFor(
                15 * 60,
                ['{{ token }}' => $this->takeFirstTokenCharacters($token)]
            );
        } catch (UnavailableTokenException) {
            // noop since [the 2nd of February 2023](https://platform.twitter.com/embed/Tweet.html?id=1621026986784337922)
        }

        return $this->contactEndpoint($endpoint);
    }

    public function ensureMemberHavingIdExists(string $memberId): MemberInterface
    {
        return $this->tweetAwareHttpClient->ensureMemberHavingIdExists($memberId);
    }

    public function ensureMemberHavingNameExists(string $memberName): MemberInterface
    {
        return $this->tweetAwareHttpClient->ensureMemberHavingNameExists($memberName);
    }

    public function extractContentErrorAsException(stdClass $content): UnavailableResourceException
    {
        $message = $content->errors[0]->message;
        $code    = $content->errors[0]->code;

        return new UnavailableResourceException($message, intval($code));
    }

    /**
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
     * @return array
     * @throws FallbackHttpAccessException
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws BlockedFromViewingMemberProfileException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \App\Twitter\Infrastructure\Exception\UnknownApiAccessException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     */
    public function fetchStatuses(array $options): array
    {
        $parameters = $this->validateRequestOptions((object) $options);

        return $this->fetchTimelineStatuses($parameters);
    }

    /**
     * @param array $parameters
     * @return array|\stdClass|null
     * @throws FallbackHttpAccessException
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws BlockedFromViewingMemberProfileException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \App\Twitter\Infrastructure\Exception\UnknownApiAccessException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     */
    public function fetchTimelineStatuses(array $parameters)
    {
        $endpoint = $this->getUserTimelineStatusesEndpoint() . '&' . implode('&', $parameters);

        return $this->contactEndpoint($endpoint);
    }

    public function getBadAuthenticationDataCode(): int
    {
        return self::ERROR_BAD_AUTHENTICATION_DATA;
    }

    public function getEmptyReplyErrorCode(): int
    {
        return self::ERROR_EMPTY_REPLY;
    }

    public function getExceededRateLimitErrorCode(): int
    {
        return self::ERROR_EXCEEDED_RATE_LIMIT;
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws FallbackHttpAccessException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws BlockedFromViewingMemberProfileException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \ReflectionException
     * @throws \App\Twitter\Infrastructure\Exception\UnknownApiAccessException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     */
    public function getListMembers(string $listId): MemberCollectionInterface
    {
        $listMembersEndpoint = $this->getListMembersEndpoint();
        $this->guardAgainstApiLimit($listMembersEndpoint);

        $sendRequest = function () use ($listMembersEndpoint, $listId) {
            return $this->contactEndpoint(strtr($listMembersEndpoint, ['{{ id }}' => $listId]));
        };

        try {
            $members = $sendRequest();
        } catch (UnavailableResourceException) {
            /**
             * @var TokenInterface $token
             */
            $token = $this->tokenRepository->findOneBy(['oauthToken' => $this->userToken]);
            $this->waitUntilTokenUnfrozen($token);

            $members = $sendRequest();
        } finally {
            return MemberCollection::fromArray($members->users ?? []);
        }
    }

    public function getTwitterErrorCodes(): array
    {
        $reflection = new \ReflectionClass(TwitterAPIAwareInterface::class);

        return $reflection->getConstants();
    }

    /**
     * @throws FallbackHttpAccessException
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws BlockedFromViewingMemberProfileException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \App\Twitter\Infrastructure\Exception\UnknownApiAccessException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     */
    public function getMemberPublishersListSubscriptions(int $memberId)
    {
        return $this->contactEndpoint(
            strtr(
                $this->getMemberListSubscriptionsEndpoint(),
                ['{{ userId }}' => $memberId]
            )
        );
    }

    /**
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

    private function reducePath(string $endpoint, string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return strtr(
            implode(
                [
                    parse_url($endpoint, PHP_URL_SCHEME),
                    '://',
                    parse_url($endpoint, PHP_URL_USER),
                    parse_url($endpoint, PHP_URL_PASS),
                    parse_url($endpoint, PHP_URL_HOST),
                    parse_url($endpoint, PHP_URL_PORT),
                    parse_url($endpoint, PHP_URL_PATH),
                ]
            ),
            [
                self::BASE_URL."{$version}/" => '',
                '.json' => ''
            ]
        );
    }

    public function setAccessToken(string $token): HttpClientInterface
    {
        $this->userToken = $token;

        return $this;
    }

    public function accessToken(): string
    {
        return $this->userToken;
    }

    public function getAccessToken(): string
    {
        return $this->userToken;
    }

    public function setAccessTokenSecret(string $secret): HttpClientInterface
    {
        $this->userSecret = $secret;

        return $this;
    }

    /**
     * @throws ApiAccessRateLimitException
     * @throws NonUniqueResultException
     */
    public function guardAgainstApiLimit(
        string $endpoint,
        bool $findNextAvailableToken = true
    ): ?TokenInterface {
        $apiLimitReached = $this->isApiLimitReached();
        $token           = null;

        $apiLimitReached = $apiLimitReached || $this->tokenRepository->isOauthTokenFrozen($this->userToken);
        if ($apiLimitReached) {
            $unfrozenToken = false;

            if ($findNextAvailableToken) {
                $token         = $this->tokenRepository->findFirstUnfrozenToken();
                $unfrozenToken = $token !== null;

                while ($apiLimitReached && $unfrozenToken) {
                    try {
                        $apiLimitReached = !$this->isApiAvailableForToken($endpoint, $token);
                    } catch (\Throwable $e) {
                        $this->logger->info($e->getMessage(), ['exception' => $e]);
                    }

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
                return new FallbackToken();
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
            $member = $this->memberRepository->findOneBy(
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
            $options['max_id'] = PHP_INT_MAX;
        }

        return $options;
    }

    /**
     * @throws Exception
     */
    public function handleResponseContentWithEmptyErrorCode(
        Exception $exception,
        TokenInterface $token
    ) {
        if ($exception instanceof \ErrorException && $exception->getSeverity() === E_WARNING) {
            $this->logger->warning($exception->getMessage(), ['exception' => $exception]);

            throw $exception;
        }

        if ($exception->getCode() === 0) {
            $emptyErrorCodeMessage   = $this->translator->trans(
                'logs.info.empty_error_code',
                ['oauth_token_start' => $this->takeFirstTokenCharacters($token)],
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
     * @throws FallbackHttpAccessException
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws BlockedFromViewingMemberProfileException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \App\Twitter\Infrastructure\Exception\UnknownApiAccessException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     */
    public function handleTwitterErrorExceptionForToken(
        string $endpoint,
        UnavailableResourceException $exception,
        callable $fetchContent
    ): array|stdClass|null
    {
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
        $this->tokenRepository->freezeToken($token);

        if (
            !str_contains($endpoint, '/statuses/user_timeline')
            && !str_contains($endpoint, '/favorites/list')
        ) {
            $this->throwException($exception);
        }

        $this->moderator->waitFor(
            15 * 60,
            ['{{ token }}' => $this->takeFirstTokenCharacters($token)]
        );

        return $this->fetchContentWithRetries($endpoint, $fetchContent);
    }

    public function isApiLimitReached(): bool
    {
        return $this->apiLimitReached;
    }

    /**
     * @throws Exception
     */
    public function isApiRateLimitReached($endpoint = '/statuses/show/:id'): bool
    {
        return false;

//        $rateLimitStatus = $this->fetchRateLimitStatus();
//
//        if (UnavailableResourceException::containErrors($rateLimitStatus)) {
//            $message = $rateLimitStatus->errors[0]->message;
//
//            $this->logger->error($message);
//            throw new Exception($message, $rateLimitStatus->errors[0]->code);
//        } else {
//            $token = new Token();
//            $token->setAccessToken($this->userToken);
//
//            $fullEndpoint = $endpoint;
//            $resourceType = 'statuses';
//
//            if (!isset($rateLimitStatus->resources->$resourceType)) {
//                return false;
//            }
//
//            $availableEndpoints = get_object_vars($rateLimitStatus->resources->$resourceType);
//            if (!array_key_exists($endpoint, $availableEndpoints)) {
//                $endpoint = null;
//
//                if (false !== strpos($fullEndpoint, '/users/show')) {
//                    $endpoint     = '/users/show/:id';
//                    $resourceType = 'users';
//                }
//
//                if (false !== strpos($fullEndpoint, '/statuses/user_timeline')) {
//                    $endpoint = '/statuses/user_timeline';
//                }
//
//                if (false !== strpos($fullEndpoint, '/lists/ownerships')) {
//                    $endpoint     = '/lists/ownerships';
//                    $resourceType = 'lists';
//                }
//
//                if (false !== strpos($fullEndpoint, '/favorites/list')) {
//                    $endpoint     = '/favorites/list';
//                    $resourceType = 'favorites';
//                }
//
//                if (false !== strpos($fullEndpoint, '/friends/ids')) {
//                    $endpoint     = '/friends/ids';
//                    $resourceType = 'friends';
//                }
//
//                if (false !== strpos($fullEndpoint, '/followers/ids')) {
//                    $endpoint     = '/followers/ids';
//                    $resourceType = 'followers';
//                }
//
//                if (false !== strpos($fullEndpoint, '/friendships/create')) {
//                    $endpoint     = '/friendships/create';
//                    $resourceType = 'friendships';
//                }
//            }
//
//            if (!is_null($endpoint) && isset($rateLimitStatus->resources->$resourceType)) {
//                $limit = $rateLimitStatus->resources->$resourceType->$endpoint->limit;
//
//                if (is_null($limit)) {
//                    return false;
//                }
//
//                $remainingCalls = $rateLimitStatus->resources->$resourceType->$endpoint->remaining;
//
//                $remainingCallsMessage = $this->translator->trans(
//                    'logs.info.calls_remaining',
//                    [
//                        'count'           => $remainingCalls,
//                        'remaining_calls' => $remainingCalls,
//                        'endpoint'        => $endpoint,
//                        'identifier'      => $this->takeFirstTokenCharacters($token),
//                    ],
//                    'logs'
//                );
//                $this->logger->info($remainingCallsMessage);
//
//                return $this->lessRemainingCallsThanTenPercentOfLimit($remainingCalls, $limit);
//            } else {
//                $this->logger->info(sprintf('Could not compute remaining calls for "%s"', $fullEndpoint));
//
//                return false;
//            }
//        }
    }

    /**
     * Returns true if there are more remaining calls than 10 % of the limit
     *
     * @param $remainingCalls
     * @param $limit
     *
     * @return bool
     */
    public function lessRemainingCallsThanTenPercentOfLimit($remainingCalls, $limit): bool
    {
        return $remainingCalls < floor($limit * 1 / 10);
    }

    public function logExceptionForToken(string $endpoint, stdClass $content, ?TokenInterface $token = null): UnavailableResourceException
    {
        $exception = $this->extractContentErrorAsException($content);

        $this->twitterApiLogger->info('[message] ' . $exception->getMessage());
        $this->twitterApiLogger->info('[code] ' . $exception->getCode());

        $token = $this->maybeGetToken($endpoint, $token);
        $this->twitterApiLogger->info('[token] ' . $token->getAccessToken());

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

    public function matchWithOneOfTwitterErrorCodes(UnavailableResourceException $exception): bool
    {
        return in_array($exception->getCode(), $this->getTwitterErrorCodes());
    }

    public function preEndpointContact(string $_): ?TokenInterface
    {
        return new FallbackToken();
    }

    /**
     * @param string $query
     *
     * @return stdClass|array
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
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
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
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

    public function fromToken(TokenInterface $token): void
    {
        $this->setAccessToken($token->getAccessToken());
        $this->setAccessTokenSecret($token->getAccessTokenSecret());

        if ($token->hasConsumerKey()) {
            $this->setConsumerKey($token->getConsumerKey());
            $this->setConsumerSecret($token->getConsumerSecret());

            $this->setUpTwitterClient(
                $token->getConsumerKey(),
                $token->getConsumerSecret(),
                $token->getAccessToken(),
                $token->getAccessTokenSecret()
            );
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

    public function setConsumerKey(string $consumerKey): self
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    public function consumerKey(): string
    {
        return $this->consumerKey;
    }

    public function setConsumerSecret(string $consumerSecret): self
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    public function setLogger(LoggerInterface $logger = null): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function setMemberRepository(MemberRepository $memberRepository): self
    {
        $this->memberRepository = $memberRepository;

        return $this;
    }

    public function setRateLimitCompliance(RateLimitCompliance $moderator = null): self
    {
        $this->moderator = $moderator;

        return $this;
    }

    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function skipCuratingTweetsForMemberHavingScreenName(string $screenName): bool
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof MemberInterface) {
            return false;
        }

        return $member->isProtected()
            || $member->hasBeenDeclaredAsNotFound()
            || $member->isSuspended();
    }

    /**
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws InvalidMemberIdentifier
     * @throws NonUniqueResultException
     * @throws TweetNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
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
            $this->memberRepository->declareMemberAsSuspended($screenName);
        } catch (NotFoundMemberException $exception) {
            $this->memberRepository->declareUserAsNotFoundByUsername($screenName);
        } catch (ProtectedAccountException $exception) {
            $this->memberRepository->declareUserAsProtected($screenName);
        } finally {
            if (isset($exception)) {
                return (object) ['ids' => []];
            }
        }
    }

    /**
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function showStatus(string $identifier): mixed
    {
        if (!is_numeric($identifier)) {
            throw new \InvalidArgumentException('A status identifier should be an integer');
        }
        $showStatusEndpoint = $this->getShowStatusEndpoint();

        try {
            return $this->contactEndpoint(strtr($showStatusEndpoint, ['{{ id }}' => $identifier]));
        } catch (TweetNotFoundException $exception) {
            $this->tweetAwareHttpClient->declareStatusNotFoundByIdentifier($identifier);

            throw $exception;
        }
    }

    /**
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws BlockedFromViewingMemberProfileException
     * @throws FallbackHttpAccessException
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
     * @throws TweetNotFoundException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws UnknownApiAccessException
     */
    public function getMemberProfile(string $identifier): stdClass
    {
        $memberIdentity = new MemberIdentity(
            $identifier,
            is_numeric($identifier) ? $identifier : MemberIdentity::NOT_PERSISTED_MEMBER_NUMERIC_ID
        );

        if ($memberIdentity->isNumeric()) {
            $this->guardAgainstSpecialMemberWithIdentifier($identifier);
        } else {
            $this->guardAgainstSpecialMembers($identifier);
        }

        try {
            $memberProfile = $this->getMemberProfileByScreenNameOrUserId($memberIdentity);
        } catch (UnavailableResourceException $exception) {
            if ($exception->getCode() === self::ERROR_SUSPENDED_USER) {
                $suspendedMember = $this->memberRepository->suspendMemberByScreenNameOrIdentifier($identifier);
                $this->logSuspendedMemberMessage($suspendedMember->twitterScreenName());

                SuspendedAccountException::raiseExceptionAboutSuspendedMemberHavingScreenName(
                    $suspendedMember->twitterScreenName(),
                    $suspendedMember->twitterId(),
                    $exception->getCode(),
                    $exception
                );
            }

            if ($exception->getCode() === self::ERROR_HTTP_NOT_FOUND) {
                NotFoundMemberException::raiseExceptionAboutTemporarilyNotFoundMemberHavingScreenName(
                    $identifier,
                    $identifier,
                    $exception->getCode(),
                    $exception
                );
            }

            if (
                $exception->getCode() === self::ERROR_NOT_FOUND
                || $exception->getCode() === self::ERROR_USER_NOT_FOUND
            ) {
                $member = $this->memberRepository->findOneBy(['twitter_username' => $memberIdentity->screenName()]);

                if (!($member instanceof MemberInterface) && $memberIdentity->screenName() !== null) {
                    $member = $this->memberRepository->declareMemberHavingScreenNameNotFound($memberIdentity->screenName());
                }

                if ($member instanceof MemberInterface && !$member->hasBeenDeclaredAsNotFound()) {
                    $this->memberRepository->declareMemberAsNotFound($member);
                }

                $this->logNotFoundMemberMessage($memberIdentity->screenName() ?? $identifier);

                NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName(
                    $identifier,
                    $identifier,
                    $exception->getCode(),
                    $exception
                );
            }

            throw $exception;
        }

        $this->guardAgainstUnavailableResource($memberProfile);

        return $memberProfile;
    }

    /**
     * @param string $screenName
     *
     * @return array|object|stdClass
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws InvalidMemberIdentifier
     * @throws NonUniqueResultException
     * @throws TweetNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function getFriendsOfMemberHavingScreenName(string $screenName)
    {
        $showUserFriendEndpoint = $this->getShowUserFriendsEndpoint();

        try {
            return $this->contactEndpoint(str_replace('{{ screen_name }}', $screenName, $showUserFriendEndpoint));
        } catch (SuspendedAccountException  $exception) {
            $this->memberRepository->declareMemberAsSuspended($screenName);
        } catch (NotFoundMemberException $exception) {
            $this->memberRepository->declareUserAsNotFoundByUsername($screenName);
        } catch (ProtectedAccountException $exception) {
            $this->memberRepository->declareUserAsProtected($screenName);
        }

        if (isset($exception)) {
            return (object) ['ids' => []];
        }
    }

    /**
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function followMember(MemberInList $member): array|stdClass|null
    {
        $endpoint = $this->getCreateFriendshipsEndpoint();

        return $this->contactEndpoint(
            str_replace(
                '{{ screen_name }}',
                $member->memberInList->twitterScreenName(),
                $endpoint
            )
        );
    }

    /**
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     */
    protected function checkApiLimit()
    {
        $lastHttpCode = $this->twitterClient->getLastHttpCode();
        $lastApiPath = $this->twitterClient->getLastApiPath();
        $lastXHeaders = $this->twitterClient->getLastXHeaders();

        if ($lastHttpCode === 404 &&
            $this->propagateNotFoundStatuses) {
            $message = sprintf(
                'A status has been removed (%s)',
                $lastApiPath
            );
            $this->twitterApiLogger->info($message);

            throw new TweetNotFoundException($message, self::ERROR_NOT_FOUND);
        }

        if ($lastHttpCode >= 400 && $lastHttpCode !== 404) {
            $this->twitterApiLogger->notice(
                sprintf(
                    '[HTTP code] %s',
                    print_r($lastHttpCode, true)
                )
            );
            $this->twitterApiLogger->notice(
                sprintf(
                    '[HTTP URL] %s',
                    $lastApiPath
                )
            );
        } else {
            $this->twitterApiLogger->info(
                sprintf(
                    '[HTTP code] %s',
                    print_r($lastHttpCode, true)
                )
            );
            $this->twitterApiLogger->info(
                sprintf(
                    '[HTTP URL] %s',
                    $lastApiPath
                )
            );
        }

        if (array_key_exists('x_rate_limit_limit', $lastXHeaders)) {
            $limit          = (int) $lastXHeaders['x_rate_limit_limit'];
            $remainingCalls = (int) $lastXHeaders['x_rate_limit_remaining'];

            $this->twitterApiLogger->info(
                sprintf(
                    '[Limit reset expected at %s]',
                    (new \DateTime())
                        ->setTimezone(
                            new \DateTimeZone('Europe/Paris')
                        )->setTimestamp((int) $lastXHeaders['x_rate_limit_reset'])
                        ->format('Y-m-d H:i')
                )
            );

            $this->apiLimitReached = $this->lessRemainingCallsThanTenPercentOfLimit(
                $remainingCalls,
                $limit
            );
        }
    }

    /**
     * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-create
     *
     * @param string $version
     *
     * @return string
     */
    protected function getCreateFriendshipsEndpoint(string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return $this->getApiBaseUrl($version) . '/friendships/create.json?screen_name={{ screen_name }}';
    }

    protected function getCreateSavedSearchEndpoint(string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return $this->getApiBaseUrl($version) . '/saved_searches/create.json?';
    }

    /**
     * @param MemberIdentity $memberIdentity
     * @return stdClass|array|null
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws BlockedFromViewingMemberProfileException
     * @throws FallbackHttpAccessException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws TweetNotFoundException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws UnknownApiAccessException
     */
    public function getMemberProfileByScreenNameOrUserId(MemberIdentity $memberIdentity): stdClass|array|null
    {
        $showUserEndpoint = $this->getShowUserEndpoint(option: ($memberIdentity->isNumeric() ? 'user_id' : 'screen_name'));
        $this->guardAgainstApiLimit($showUserEndpoint);

        return $this->contactEndpoint(
            strtr(
                $showUserEndpoint,
                [
                    '{{ screen_name }}' => $memberIdentity->isNumeric() ? '' : $memberIdentity->screenName(),
                    '{{ user_id }}'     => $memberIdentity->isNumeric() ? $memberIdentity->id() : ''
                ]
            )
        );
    }

    public function intendingToAddMemberToList(string $endpoint): bool
    {
        // [Enables the authenticated user to add a member to a List they own.](https://developer.twitter.com/en/docs/twitter-api/lists/list-members/api-reference/post-lists-id-members)
        $matchingResult = preg_match('#\/lists\/\d+\/members#', $endpoint);

        return $matchingResult !== false && $matchingResult > 0;
    }

    public function intendingToFetchMemberProfile(string $endpoint): bool
    {
        $matchingResult = preg_match('#\/users\/show#', $endpoint);

        return $matchingResult !== false && $matchingResult > 0;
    }

    public function whichTwitterAPIVersionToCall(bool $intendingToToAddMemberToList): string
    {
        if ($intendingToToAddMemberToList) {
            return self::TWITTER_API_VERSION_2;
        }

        return self::TWITTER_API_VERSION_1_1;
    }

    public function whichHttpMethod(bool $intendingToToAddMemberToList, string $endpoint): string
    {
        if ($intendingToToAddMemberToList
            || str_contains($endpoint, 'create.json')
            || str_contains($endpoint, 'create_all.json')
            || str_contains($endpoint, 'destroy.json')
            || str_contains($endpoint, 'destroy_all.json')
            || str_contains($endpoint, '/graphql')
        ) {
            return self::HTTP_METHOD_POST;
        }

        return self::HTTP_METHOD_GET;
    }

    protected function getDestroyFriendshipsEndpoint(string $version = self::TWITTER_API_VERSION_1_1)
    {
        return $this->getApiBaseUrl($version) . '/friendships/destroy.json?screen_name={{ screen_name }}';
    }

    protected function getLikesEndpoint(string $version = self::TWITTER_API_VERSION_1_1)
    {
        return $this->getApiBaseUrl($version) . '/favorites/list.json?' .
            'tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0';
    }

    /**
     * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-members
     */
    protected function getListMembersEndpoint(string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return sprintf(
          '%s%s%s',
            $this->getApiBaseUrl($version),
            self::API_ENDPOINT_GET_MEMBERS_LISTS,
            '.json?count=5000&list_id={{ id }}'
        );
    }

    protected function getRateLimitStatusEndpoint(string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return $this->getApiBaseUrl($version) . self::API_ENDPOINT_RATE_LIMIT_STATUS. '.json?' .
            'resources=favorites,statuses,users,lists,friends,friendships,followers';
    }

    protected function getSearchEndpoint(string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return $this->getApiBaseUrl($version) . '/search/tweets.json?tweet_mode=extended&';
    }

    protected function getShowMemberSubscribeesEndpoint(string $version = self::TWITTER_API_VERSION_1_1)
    {
        return $this->getApiBaseUrl($version) . '/followers/ids.json?count=5000&screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function getShowStatusEndpoint(string $version = self::TWITTER_API_VERSION_1_1)
    {
        return $this->getApiBaseUrl($version) . '/statuses/show.json?id={{ id }}&tweet_mode=extended&include_entities=true';
    }

    protected function getShowUserEndpoint(string $version = self::TWITTER_API_VERSION_1_1, $option = 'screen_name'): string
    {
        if ($option === 'screen_name') {
            $parameters = 'screen_name={{ screen_name }}';
        } else {
            $parameters = 'user_id={{ user_id }}';
        }

        return $this->getApiBaseUrl($version) . '/users/show.json?' . $parameters;
    }

    protected function getShowUserFriendsEndpoint(string $version = self::TWITTER_API_VERSION_1_1)
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

    protected function getUserListsEndpoint(string $version = self::TWITTER_API_VERSION_1_1)
    {
        return $this->getApiBaseUrl($version) . '/lists/list.json?reverse={{ reverse }}&screen_name={{ screenName }}';
    }

    protected function getUserTimelineStatusesEndpoint(string $version = self::TWITTER_API_VERSION_1_1)
    {
        return $this->getApiBaseUrl($version) . '/statuses/user_timeline.json?' .
            'tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0';
    }

    protected function guardAgainstProtectedAccount(stdClass $twitterUser): ?bool
    {
        if (!isset($twitterUser->protected) || !$twitterUser->protected) {
            return true;
        }

        $this->memberRepository->declareUserAsProtected($twitterUser->screen_name, $twitterUser->id_str);

        ProtectedAccountException::raiseExceptionAboutProtectedMemberHavingScreenName(
            $twitterUser->screen_name,
            $twitterUser->id_str,
            self::ERROR_PROTECTED_ACCOUNT
        );
    }

    /**
     * @throws UnavailableResourceException
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
     */
    protected function isApiAvailable($endpoint): bool
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
                    $this->tokenRepository->freezeToken(
                        FreezableToken::fromAccessToken(
                            $this->accessToken(),
                            $this->consumerKey()
                        )
                    );
            }
        }

        return $availableApi;
    }

    protected function isApiAvailableForToken($endpoint, TokenInterface $token): bool
    {
        $this->setAccessToken($token->getAccessToken());
        $this->setAccessTokenSecret($token->getAccessTokenSecret());
        $this->setConsumerKey($token->getConsumerKey());
        $this->setConsumerSecret($token->getConsumerSecret());

        return $this->isApiAvailable($endpoint);
    }

    protected function takeFirstTokenCharacters(TokenInterface $token): string
    {
        return substr($token->getAccessToken(), 0, 8);
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
    protected function validateRequestOptions(stdClass $options): array
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
     * @throws ApiAccessRateLimitException
     */
    protected function waitUntilTokenUnfrozen(TokenInterface $token)
    {
        if ($this->shouldRaiseExceptionOnApiLimit) {
            throw new ApiAccessRateLimitException('Impossible to access the source API at the moment');
        }

        $now = new \DateTime;
        $this->moderator->waitFor(
            $token->getFrozenUntil()->getTimestamp() - $now->getTimestamp(),
            ['{{ token }}' => $this->takeFirstTokenCharacters($token)]
        );
    }

    private function convertExceptionIntoContent($exception): stdClass
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
     * @throws \Exception
     */
    private function fetchContent(string $endpoint): object|array
    {
        $intendingToAddMemberToList = $this->intendingToAddMemberToList($endpoint);

        if (
            str_contains($endpoint, '/graphql') ||
            $this->whichHttpMethod($intendingToAddMemberToList, $endpoint) === self::HTTP_METHOD_GET ||
            $this->intendingToFetchMemberProfile($endpoint)
        ) {
            if (str_contains($endpoint, self::API_ENDPOINT_GET_MEMBER_PROFILE)) {
                $parameters = $this->reduceParameters($endpoint, []);

                return $this->fallbackHttpClient->getMemberProfile(
                    new MemberIdentity(
                        $parameters['screen_name'],
                        MemberIdentity::NOT_PERSISTED_MEMBER_NUMERIC_ID
                    )
                );
            }

            if (str_contains($endpoint, self::API_ENDPOINT_MEMBER_TIMELINE) || str_contains($endpoint, '/graphql')) {
                $parameters = $this->reduceParameters($endpoint, []);

                return $this->fallbackHttpClient->getMemberTimeline(
                    new MemberIdentity($parameters['screen_name'], MemberIdentity::NOT_PERSISTED_MEMBER_NUMERIC_ID),
                    $this->memberRepository
                )->toArray();
            }

            if (str_contains($endpoint, self::API_ENDPOINT_OWNERSHIPS)) {
                $parameters = $this->reduceParameters($endpoint, []);

                $ownershipCollection = $this->fallbackHttpClient->getMemberOwnerships(
                    new ListsBatchSelector($parameters['screen_name'])
                );

                return (object) [
                    'lists' => $ownershipCollection->toArray(),
                    'next_cursor' => $ownershipCollection->nextPage()
                ];
            }
        }

        $token = $this->preEndpointContact($endpoint);

        return $this->contactEndpointUsingConsumerKey($endpoint, $token);
    }

    /**
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws UnknownApiAccessException
     * @throws BlockedFromViewingMemberProfileException
     * @throws FallbackHttpAccessException
     */
    private function fetchContentWithRetries(
        string $endpoint,
        callable $fetchContent
    ): array|stdClass|null
    {
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
            } catch (OverCapacityException) {
                $this->logger->info(
                    sprintf(
                        'About to retry making contact with endpoint (retry #%d out of %d) "%s"',
                        $retries + 1,
                        self::MAX_RETRIES,
                        $endpoint
                    )
                );
            } finally {
                $retries++;
            }
        }

        return $content;
    }

    private function getMemberListSubscriptionsEndpoint(string $version = self::TWITTER_API_VERSION_1_1): string
    {
        return $this->getApiBaseUrl($version) . '/lists/subscriptions.json?cursor=-1&count=800&user_id={{ userId }}';
    }

    /**
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     */
    private function guardAgainstSpecialMemberWithIdentifier(string $identifier): void
    {
        $member = $this->memberRepository->findOneBy(['twitterID' => $identifier]);
        if ($member instanceof MemberInterface) {
            if ($member->isSuspended()) {
                $this->logSuspendedMemberMessage($member->twitterScreenName());
                SuspendedAccountException::raiseExceptionAboutSuspendedMemberHavingScreenName(
                    $member->twitterScreenName(),
                    $member->twitterId(),
                    self::ERROR_SUSPENDED_ACCOUNT
                );
            }

            if ($member->hasNotBeenDeclaredAsNotFound()) {
                $this->logNotFoundMemberMessage($member->twitterScreenName());
                NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName(
                    $member->twitterScreenName(),
                    $member->twitterId(),
                    self::ERROR_NOT_FOUND
                );
            }

            if ($member->isProtected()) {
                $this->logProtectedMemberMessage($member->twitterScreenName());
                ProtectedAccountException::raiseExceptionAboutProtectedMemberHavingScreenName(
                    $member->twitterScreenName(),
                    $member->twitterId(),
                    self::ERROR_PROTECTED_ACCOUNT
                );
            }
        }
    }

    /**
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     */
    private function guardAgainstSpecialMembers(string $screenName): void
    {
        $member = $this->memberRepository->findOneBy(['twitter_username' => $screenName]);
        if ($member instanceof MemberInterface) {
            if ($member->isSuspended()) {
                $this->logSuspendedMemberMessage($screenName);
                SuspendedAccountException::raiseExceptionAboutSuspendedMemberHavingScreenName(
                    $screenName,
                    $member->twitterId(),
                    self::ERROR_SUSPENDED_ACCOUNT
                );
            }

            if ($member->hasBeenDeclaredAsNotFound()) {
                $this->logNotFoundMemberMessage($screenName);
                NotFoundMemberException::raiseExceptionAboutNotFoundMemberHavingScreenName(
                    $screenName,
                    $member->twitterId(),
                    self::ERROR_NOT_FOUND
                );
            }

            if ($member->isProtected()) {
                $this->logProtectedMemberMessage($screenName);
                ProtectedAccountException::raiseExceptionAboutProtectedMemberHavingScreenName(
                    $screenName,
                    $member->twitterId(),
                    self::ERROR_PROTECTED_ACCOUNT
                );
            }
        }
    }

    private function logNotFoundMemberMessage($screenName)
    {
        $notFoundMemberMessage = $this->translator->trans(
            'amqp.output.not_found_member',
            ['{{ user }}' => $screenName],
            'messages'
        );

        $this->logger->info($notFoundMemberMessage);
    }

    private function logProtectedMemberMessage(string $screenName)
    {
        $protectedMemberMessage = $this->translator->trans(
            'amqp.output.protected_member',
            ['{{ user }}' => $screenName],
            'messages'
        );

        $this->logger->info($protectedMemberMessage);
    }

    private function logSuspendedMemberMessage(string $screenName)
    {
        $suspendedMessageMessage = $this->translator->trans(
            'amqp.output.suspended_account',
            ['{{ user }}' => $screenName],
            'messages'
        );

        $this->logger->info($suspendedMessageMessage);
    }

    private function maybeGetToken(string $endpoint, TokenInterface $token = null): TokenInterface
    {
        if ($this->whichHttpMethod($this->intendingToAddMemberToList($endpoint), $endpoint) === self::HTTP_METHOD_GET) {
            return new NullToken();
        }

        if ($token instanceof TokenInterface) {
            return $token;
        }

        return $this->preEndpointContact($endpoint);
    }

    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $accessTokenKey
     * @param string $accessTokenSecret
     */
    private function setUpTwitterClient(
        string $consumerKey,
        string $consumerSecret,
        string $accessTokenKey,
        string $accessTokenSecret
    ): void {
        $this->twitterClient = new BaseTwitterApiClient(
            $consumerKey,
            $consumerSecret,
            $accessTokenKey,
            $accessTokenSecret
        );
    }
}

<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Api;

use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Accessor\StatusAccessor;
use App\Accessor\Exception\ApiRateLimitingException;

use App\Member\Entity\AggregateSubscription;
use App\Member\MemberInterface;
use App\Status\LikedStatusCollectionAwareInterface;
use Doctrine\Common\Persistence\ObjectRepository;

use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;

use WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\EmptyErrorCodeException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\OverCapacityException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;

use TwitterOauth;
use WTW\UserBundle\Entity\User;
use WTW\UserBundle\Repository\UserRepository;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Accessor implements TwitterErrorAwareInterface, LikedStatusCollectionAwareInterface
{
    const ERROR_PROTECTED_ACCOUNT = 2048;

    const MAX_RETRIES = 5;

    /**
     * @var StatusAccessor
     */
    public $statusAccessor;

    /**
     * @var \Goutte\Client $httpClient
     */
    public $httpClient;

    /**
     * @var
     */
    public $httpClientClass;

    /**
     * @var
     */
    public $clientClass;

    /**
     * @var string
     */
    public $environment = 'dev';

    /**
     * @var string
     */
    protected $apiHost = 'api.twitter.com';

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    public $twitterApiLogger;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Moderator\ApiLimitModerator $moderator
     */
    protected $moderator;

    /**
     * @var string
     */
    public $userToken;

    /**
     * @var
     */
    protected $userSecret;

    /**
     * @var
     */
    protected $consumerKey;

    /**
     * @var
     */
    protected $consumerSecret;

    /**
     * @var
     */
    protected $authenticationHeader;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    protected $tokenRepository;

    /**
     * @var UserRepository
     */
    public $userRepository;

    /**
     * @var \Symfony\Component\Translation\Translator $translator
     */
    protected $translator;

    /**
     * @var bool
     */
    public $propagateNotFoundStatuses = false;

    /**
     * @var bool
     */
    public $shouldRaiseExceptionOnApiLimit = false;

    /**
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * @param null $logger
     * @return $this
     */
    public function setLogger($logger = null)
    {
        if (!is_null($logger)) {
            $this->logger = $logger;
        }

        return $this;
    }

    /**
     * @param null $moderator
     * @return $this
     */
    public function setModerator($moderator = null)
    {
        if (!is_null($moderator)) {
            $this->moderator = $moderator;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserSecret()
    {
        return $this->userSecret;
    }

    /**
     * @return mixed
     */
    public function getUserToken()
    {
        return $this->userToken;
    }

    /**
     * @param string $consumerKey
     * @return $this
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    /**
     * @param string $consumerSecret
     * @return $this
     */
    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    /**
     * @param string $userSecret
     * @return $this
     */
    public function setUserSecret($userSecret)
    {
        $this->userSecret = $userSecret;

        return $this;
    }

    /**
     * @param string $userToken
     * @return $this
     */
    public function setUserToken($userToken)
    {
        $this->userToken = $userToken;

        return $this;
    }

    /**
     * @param string $header
     * @return $this
     */
    public function setAuthenticationHeader($header)
    {
        $this->authenticationHeader = $header;

        return $this;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setApiHost($host)
    {
        $this->apiHost = $host;

        return $this;
    }

    /**
     * @param string $clientClass
     * @return $this
     */
    public function setClientClass($clientClass)
    {
        $this->clientClass = $clientClass;

        return $this;
    }

    /**
     * @param string $httpClientClass
     * @return $this
     */
    public function setHttpClientClass($httpClientClass)
    {
        $this->httpClientClass = $httpClientClass;

        return $this;
    }

    /**
     * @param ObjectRepository $tokenRepository
     * @return $this
     */
    public function setTokenRepository(ObjectRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

    /**
     * @param array $criteria
     * @return bool
     */
    public function isAboutToCollectLikesFromCriteria(array $criteria): bool
    {
        if (!array_key_exists(self::INTENT_TO_FETCH_LIKES, $criteria)) {
            return false;
        }

        return $criteria[self::INTENT_TO_FETCH_LIKES];
    }

    /**
     * Fetch timeline statuses
     *
     * @param null|array|object $options
     * @return \API|mixed|object
     * @throws \Exception
     */
    public function fetchStatuses(array $options)
    {
        if (is_null($options) || (!is_object($options) && !is_array($options))) {
            throw new \Exception('Invalid options');
        }

        if (is_array($options)) {
            $options = (object)$options;
        }

        $parameters = $this->validateRequestOptions($options);

        if ($this->isAboutToCollectLikesFromCriteria((array)$options)) {
            return $this->fetchLikes($parameters);
        }

        return $this->fetchTimelineStatuses($parameters);
    }

    /**
     * @param array $parameters
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function fetchTimelineStatuses(array $parameters)
    {
        $endpoint = $this->getUserTimelineStatusesEndpoint() . '&' . implode('&', $parameters);

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param array $parameters
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function fetchLikes(array $parameters)
    {
        $endpoint = $this->getLikesEndpoint() . '&' . implode('&', $parameters);

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param string $query
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveSearch(string $query)
    {
        $endpoint = $this->getCreateSavedSearchEndpoint()."query=$query";

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param AggregateSubscription $subscription
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @param array $members
     * @param int   $listId
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addMembersToList(array $members, int $listId)
    {
        if (count($members) > 100) {
            throw new \LogicException('No more than 100 members can be added to a list at once');
        }

        $endpoint = $this->getAddMembersToListEndpoint().
            "screen_name=".implode(',', $members).
            '&list_id='.$listId
        ;

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param string $query
     * @param string $params
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function search(string $query, $params = '')
    {
        $endpoint = $this->getSearchEndpoint()."q=$query&count=100".$params;

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param array $options
     * @param bool  $shouldDiscoverFutureStatuses
     * @return array
     */
    public function guessMaxId(array $options, bool $shouldDiscoverFutureStatuses)
    {
        if ($shouldDiscoverFutureStatuses) {
            $member = $this->userRepository->findOneBy(
                ['twitter_username' => $options['screen_name']]
            );
            if (($member instanceof User) && !is_null($member->maxStatusId)) {
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
     * @param $endpoint
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function contactEndpoint($endpoint)
    {
        $response = null;

        $fetchContent = function ($endpoint) {
            try {
                return $this->fetchContent($endpoint);
            } catch (ConnectException | \Exception $exception) {
                $this->logger->error($exception->getMessage(), $exception->getTrace());

                if ($exception instanceof ConnectException) {
                    throw $exception;
                }

                if ($this->propagateNotFoundStatuses &&
                    ($exception instanceof NotFoundStatusException)) {
                    throw $exception;
                }

                return $this->convertExceptionIntoContent($exception);
            }
        };

        $content = $this->fetchContentWithRetries($endpoint, $fetchContent);

        if (!$this->hasError($content)) {
            return $content;
        }

        $loggedException = $this->logExceptionForToken($endpoint, $content);
        if ($this->matchWithOneOfTwitterErrorCodes($loggedException)) {
            return $this->handleTwitterErrorExceptionForToken($endpoint, $loggedException, $fetchContent);
        }

        return $this->delayUnknownExceptionHandlingOnEndpointForToken($endpoint);
    }

    /**
     * @param $endpoint
     * @param Token|null $token
     * @return \API|mixed|object
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function delayUnknownExceptionHandlingOnEndpointForToken($endpoint, Token $token = null)
    {
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
     * @param string                       $endpoint
     * @param UnavailableResourceException $exception
     * @param callable                     $fetchContent
     * @return mixed
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function handleTwitterErrorExceptionForToken(
        string $endpoint,
        UnavailableResourceException $exception,
        callable $fetchContent
    ) {
        if ($exception->getCode() !== self::ERROR_EXCEEDED_RATE_LIMIT) {
            $this->throwException($exception);
        }

        $token = $this->maybeGetToken($endpoint);
        $this->tokenRepository->freezeToken($token->getOauthToken());

        if (strpos($endpoint, '/statuses/user_timeline') === false &&
            strpos($endpoint, '/favorites/list') === false
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
     * @param UnavailableResourceException $exception
     * @return bool
     * @throws \ReflectionException
     */
    public function matchWithOneOfTwitterErrorCodes(UnavailableResourceException $exception)
    {
        return in_array($exception->getCode(), $this->getTwitterErrorCodes());
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function getTwitterErrorCodes()
    {
        $reflection = new \ReflectionClass(__NAMESPACE__ . '\TwitterErrorAwareInterface');

        return $reflection->getConstants();
    }

    /**
     * @param string     $endpoint
     * @param \stdClass  $content
     * @param Token|null $token
     * @return UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function logExceptionForToken(string $endpoint, \stdClass $content, Token $token = null)
    {
        $exception = $this->extractContentErrorAsException($content);

        $this->twitterApiLogger->info('[message] ' . $exception->getMessage());
        $this->twitterApiLogger->info('[code] ' . $exception->getCode());

        $token = $this->maybeGetToken($endpoint, $token);
        $this->twitterApiLogger->info('[token] ' . $token->getOauthToken());

        return $exception;
    }

    /**
     * @param \stdClass $content
     * @return UnavailableResourceException
     */
    public function extractContentErrorAsException(\stdClass $content)
    {
        $message = $content->errors[0]->message;
        $code = $content->errors[0]->code;

        return new UnavailableResourceException($message, $code);
    }

    /**
     * @param  string $endpoint
     * @return mixed
     * @throws \Exception
     */
    public function contactEndpointUsingBearerToken($endpoint)
    {
        $this->httpClient->setHeader('Authorization', $this->authenticationHeader);
        $this->httpClient->request('GET', $endpoint);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->httpClient->getResponse();
        $encodedContent = $response->getContent();
        $decodedContent = json_decode($encodedContent);

        $jsonLastError = json_last_error();
        if ($jsonLastError !== JSON_ERROR_NONE) {
            throw new \Exception(sprintf('Could not decode content with error %d', $jsonLastError));
        }

        return $decodedContent;
    }

    /**
     * @param string $endpoint
     * @param Token $token
     * @param array $tokens
     * @return object|\stdClass
     * @throws \Exception
     */
    public function contactEndpointUsingConsumerKey($endpoint, Token $token, array $tokens)
    {
        $connection = $this->makeHttpClient($tokens);

        try {
            $content = $this->connectToEndpoint($connection, $endpoint);
            $this->checkApiLimit($connection);
        } catch (\Exception $exception) {
            $content = $this->handleResponseContentWithEmptyErrorCode($exception, $token);
        }

        return $content;
    }

    /**
     * @param array $tokens
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
     * @param TwitterOauth $client
     * @param string $endpoint
     * @param array $parameters
     * @return \stdClass
     */
    public function connectToEndpoint(TwitterOAuth $client, $endpoint, $parameters = [])
    {
        if (strpos($endpoint, 'create.json') !== false
        || strpos($endpoint, 'create_all.json') !== false
        ) {
            return $client->post($endpoint, $parameters);
        }

        return $client->get($endpoint, $parameters);
    }

    /**
     * @return bool
     */
    public function shouldUseBearerToken()
    {
        return !is_null($this->authenticationHeader);
    }

    /**
     * @param array $tokens
     * @param       $endpoint
     * @return Token
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function preEndpointContact(array $tokens, $endpoint)
    {
        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token */
        $token = $this->tokenRepository->refreshFreezeCondition($tokens['oauth'], $this->logger);

        if (!$token->isFrozen()) {
            return $token;
        }

        return $this->guardAgainstApiLimit($endpoint);
    }

    /**
     * @param \Exception $exception
     * @param Token $token
     * @return object
     * @throws \Exception
     */
    public function handleResponseContentWithEmptyErrorCode(\Exception $exception, Token $token)
    {
        if ($exception->getCode() === 0) {
            $emptyErrorCodeMessage = $this->translator->trans(
                'logs.info.empty_error_code',
                ['{{ oauth token start }}' => $this->takeFirstTokenCharacters($token)],
                'logs'
            );
            $emptyErrorCodeException = EmptyErrorCodeException::encounteredWhenUsingToken(
                $emptyErrorCodeMessage,
                self::getExceededRateLimitErrorCode(),
                $exception
            );
            $this->logger->info($emptyErrorCodeException->getMessage());

            return $this->makeContentOutOfException($emptyErrorCodeException);
        } else {
            throw $exception;
        }
    }

    /**
     * @param UnavailableResourceException $exception
     * @return object
     */
    public function makeContentOutOfException(UnavailableResourceException $exception)
    {
        return (object)[
            'errors' => [
                (object)[
                    'message' => $exception->getMessage(),
                    'code' => self::getExceededRateLimitErrorCode(),
                ],
            ],
        ];
    }

    public function setupClient()
    {
        $requesterClass = $this->clientClass;
        $this->httpClient = new $requesterClass();

        $httpClientClass = $this->httpClientClass;
        $httpClient = new $httpClientClass();
        $this->httpClient->setClient($httpClient);
    }

    /**
     * @param string $screenName
     * @return bool
     */
    public function shouldSkipSerializationForMemberWithScreenName(string $screenName)
    {
        $member = $this->userRepository->findOneBy(['twitter_username' => $screenName]);
        if (!$member instanceof MemberInterface) {
            return false;
        }

        return $member->isProtected() ||
            $member->hasBeenDeclaredAsNotFound() ||
            $member->isSuspended() ||
            $member->isAWhisperer()
        ;
    }

    /**
     * @param $identifier
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function showUser($identifier)
    {
        $screenName = null;
        $userId = null;

        if (is_integer($identifier)) {
            $userId = $identifier;
            $option = 'user_id';

            $this->guardAgainstSpecialMemberWithIdentifier($identifier);
        } else {
            $screenName = $identifier;
            $option = 'screen_name';

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
                        '{{ user_id }}' => $userId
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

            if ($exception->getCode() === self::ERROR_NOT_FOUND ||
                $exception->getCode() === self::ERROR_USER_NOT_FOUND
            ) {
                $member = $this->userRepository->findOneBy(['twitter_username' => $screenName]);
                if (!($member instanceof MemberInterface) && !is_null($screenName)) {
                    $member = $this->userRepository->declareMemberHavingScreenNameNotFound($screenName);
                }

                if ($member instanceof MemberInterface && !$member->isNotFound()) {
                    $this->userRepository->declareUserAsNotFound($member);
                }

                $this->logNotFoundMemberMessage(is_null($screenName) ? $identifier : $screenName);
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
     * @param string $version
     * @return string
     */
    protected function getUserTimelineStatusesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/user_timeline.json?' .
            'tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getLikesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/favorites/list.json?' .
            'tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getCreateSavedSearchEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/saved_searches/create.json?';
    }

    /**
     * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-create
     *
     * @param string $version
     * @return string
     */
    protected function getCreateFriendshipsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/friendships/create.json?screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getSearchEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/search/tweets.json?tweet_mode=extended&';
    }

    /**
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function fetchRateLimitStatus()
    {
        $endpoint = $this->getRateLimitStatusEndpoint();

        return $this->contactEndpoint($endpoint);
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getRateLimitStatusEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/application/rate_limit_status.json?'.
            'resources=favorites,statuses,users,lists,friends,friendships,followers';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getApiBaseUrl($version = '1.1')
    {
        return 'https://' . $this->apiHost . '/' . $version;
    }

    /**
     * @param $options
     * @return array
     */
    protected function validateRequestOptions($options)
    {
        $validatedOptions = [];

        if (isset($options->{'count'})) {
            $resultsCount = $options->{'count'};
        } else {
            $resultsCount = 1;
        }
        $validatedOptions[] = 'count' . '=' . $resultsCount;

        if (isset($options->{'max_id'})) {
            $maxId = $options->{'max_id'};
            $validatedOptions[] = 'max_id' . '=' . $maxId;
        }

        if (isset($options->{'screen_name'})) {
            $screenName = $options->{'screen_name'};
            $validatedOptions[] = 'screen_name' . '=' . $screenName;
        }

        if (isset($options->{'since_id'})) {
            $sinceId = $options->{'since_id'};
            $validatedOptions[] = 'since_id' . '=' . $sinceId;
        }

        return $validatedOptions;
    }

    /**
     * @param UnavailableResourceException $exception
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
     * @return array
     * @throws \Exception
     */
    protected function getTokens()
    {
        if (null !== $this->userSecret && null !== $this->userToken) {
            $tokens = [
                'oauth' => $this->userToken,
                'oauth_secret' => $this->userSecret,
                'key' => $this->consumerKey,
                'secret' => $this->consumerSecret,
            ];
        } else {
            throw new \Exception('Invalid tokens');
        }

        return $tokens;
    }

    /**
     * @param $twitterUser
     * @return bool
     * @throws ProtectedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function guardAgainstUnavailableResource($twitterUser)
    {
        $validUser = is_object($twitterUser);

        if ($validUser && !isset($twitterUser->errors)) {
            return $this->guardAgainstProtectedAccount($twitterUser);
        }

        if ($validUser && isset($twitterUser->errors)) {
            $errorCode = $twitterUser->errors[0]->code;
            $errorMessage = $twitterUser->errors[0]->message;
            $this->logger->error($errorMessage);

            $this->throwUnavailableResourceException($errorMessage, $errorCode);
        }

        $errorMessage = 'Unavailable user';
        $this->logger->info($errorMessage);

        $this->throwUnavailableResourceException($errorMessage, 0);
    }

    /**
     * @param $twitterUser
     * @return bool
     * @throws ProtectedAccountException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @param $errorMessage
     * @param $errorCode
     * @throws UnavailableResourceException
     */
    protected function throwUnavailableResourceException($errorMessage, $errorCode)
    {
        throw new UnavailableResourceException($errorMessage, $errorCode);
    }

    /**
     * @param string $version
     * @param string $option
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
     * @param string $screenName
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
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
                return (object)['ids' => []];
            }
        }
    }


    /**
     * @param string $screenName
     * @param int    $cursor
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function showMemberSubscribees(string $screenName, int $cursor = -1)
    {
        $showUserFriendEndpoint = $this->getShowMemberSubscribeesEndpoint();

        try {
            return $this->contactEndpoint(
                str_replace('{{ screen_name }}',
                    $screenName,
                    $showUserFriendEndpoint
                ).'&cursor='.$cursor
            );
        } catch (SuspendedAccountException  $exception) {
            $this->userRepository->declareMemberAsSuspended($screenName);
        } catch (NotFoundMemberException $exception) {
            $this->userRepository->declareUserAsNotFoundByUsername($screenName);
        } catch (ProtectedAccountException $exception) {
            $this->userRepository->declareUserAsProtected($screenName);
        } finally {
            if (isset($exception)) {
                return (object)['ids' => []];
            }
        }
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getShowUserFriendsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/friends/ids.json?count=5000&screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getShowMemberSubscribeesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/followers/ids.json?count=5000&screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getShowStatusEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/show.json?id={{ id }}&tweet_mode=extended';
    }

    /**
     * @param $identifier
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
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
     * @param $screenName
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function getUserLists($screenName)
    {
        return $this->contactEndpoint(
            strtr(
                $this->getUserListsEndpoint(),
                [
                    '{{ screenName }}' => $screenName,
                    '{{ reverse }}' => true,
                ]
            )
        );
    }

    /**
     * @param $screenName
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @param string $version
     * @return string
     */
    protected function getUserListsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/list.json?reverse={{ reverse }}&screen_name={{ screenName }}';
    }

    /**
     * @param string $version
     * @return string
     */
    private function getMemberListSubscriptionsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/subscriptions.json?count=1000&screen_name={{ screenName }}';
    }

    /**
     * @param     $screenName
     * @param int $cursor
     * @return \API|mixed|object|\stdClass
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     */
    public function getUserOwnerships($screenName, $cursor = -1)
    {
        $endpoint = $this->getUserOwnershipsEndpoint();
        $this->guardAgainstApiLimit($endpoint);

        return $this->contactEndpoint(
            strtr(
                $endpoint,
                [
                    '{{ screenName }}' => $screenName,
                    '{{ reverse }}' => true,
                    '{{ count }}' => 1000,
                    '{{ cursor }}' => $cursor,
                ]
            )
        );
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getUserOwnershipsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/ownerships.json?reverse={{ reverse }}' .
            '&screen_name={{ screenName }}' .
            '&count={{ count }}&cursor={{ cursor }}';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getAddMembersToListEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/members/create_all.json' .
            '?';
    }

    /**
     * @param $id
     * @return \API|array|mixed|object|\stdClass
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getListMembers($id)
    {
        $listMembersEndpoint = $this->getListMembersEndpoint();
        $this->guardAgainstApiLimit($listMembersEndpoint, $findNextAvailableToken = false);

        $sendRequest = function () use ($listMembersEndpoint, $id) {
            return $this->contactEndpoint(strtr($listMembersEndpoint, ['{{ id }}' => $id]));
        };

        $members = [];

        try {
            $members = $sendRequest();
        } catch (UnavailableResourceException $exception) {
            /**
             * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token
             */
            $token = $this->tokenRepository->findOneBy(['oauthToken' => $this->userToken]);
            $this->waitUntilTokenUnfrozen($token);

            $members = $sendRequest();
        } finally {
            return $members;
        }
    }

    /**
     * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-members
     * @param string $version
     * @return string
     */
    protected function getListMembersEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/members.json?count=5000&list_id={{ id }}';
    }

    /**
     * @param string $endpoint
     * @return bool
     * @throws \Exception
     */
    public function isApiRateLimitReached($endpoint = '/statuses/show/:id')
    {
        $rateLimitStatus = $this->fetchRateLimitStatus();

        if ($this->hasError($rateLimitStatus)) {
            $message = $rateLimitStatus->errors[0]->message;

            $this->logger->error($message);
            throw new \Exception($message, $rateLimitStatus->errors[0]->code);
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
                    $endpoint = "/users/show/:id";
                    $resourceType = 'users';
                }

                if (false !== strpos($fullEndpoint, '/statuses/user_timeline')) {
                    $endpoint = "/statuses/user_timeline";
                }

                if (false !== strpos($fullEndpoint, '/lists/ownerships')) {
                    $endpoint = "/lists/ownerships";
                    $resourceType = 'lists';
                }

                if (false !== strpos($fullEndpoint, '/favorites/list')) {
                    $endpoint = "/favorites/list";
                    $resourceType = 'favorites';
                }

                if (false !== strpos($fullEndpoint, '/friends/ids')) {
                    $endpoint = "/friends/ids";
                    $resourceType = 'friends';
                }

                if (false !== strpos($fullEndpoint, '/followers/ids')) {
                    $endpoint = "/followers/ids";
                    $resourceType = 'followers';
                }

                if (false !== strpos($fullEndpoint, '/friendships/create')) {
                    $endpoint = "/friendships/create";
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

                $remainingCallsMessage = $this->translator->transChoice(
                    'logs.info.calls_remaining',
                    $remainingCalls,
                    [
                        '{{ count }}' => $remainingCalls,
                        '{{ endpoint }}' => $endpoint,
                        '{{ identifier }}' => $this->takeFirstTokenCharacters($token),
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
     * @return bool
     */
    public function lessRemainingCallsThanTenPercentOfLimit($remainingCalls, $limit)
    {
        return $remainingCalls < floor($limit * 1 / 10);
    }

    /**
     * @param $response
     * @return bool
     */
    public function hasError($response)
    {
        return is_object($response) && (
            isset($response->errors) &&
            is_array($response->errors) &&
            isset($response->errors[0])
        || isset($response->error));
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
                $limit = (int)$connection->http_header['x_rate_limit_limit'];
                $remainingCalls = (int)$connection->http_header['x_rate_limit_remaining'];

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
     * @return int
     */
    public function getMemberNotFoundErrorCode()
    {
        return self::ERROR_NOT_FOUND;
    }

    /**
     * @return int
     */
    public function getUserNotFoundErrorCode()
    {
        return self::ERROR_USER_NOT_FOUND;
    }

    /**
     * @return int
     */
    public function getSuspendedUserErrorCode()
    {
        return self::ERROR_SUSPENDED_USER;
    }

    /**
     * @return int
     */
    public function getProtectedAccountErrorCode()
    {
        return self::ERROR_PROTECTED_ACCOUNT;
    }

    /**
     * @var boolean
     */
    protected $apiLimitReached = false;

    public function isApiLimitReached()
    {
        return $this->apiLimitReached;
    }

    /**
     * @param      $endpoint
     * @param bool $findNextAvailableToken
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function guardAgainstApiLimit($endpoint, $findNextAvailableToken = true)
    {
        $apiLimitReached = $this->isApiLimitReached();
        $token = null;

        try {
            $apiLimitReached = $apiLimitReached || $this->tokenRepository->isOauthTokenFrozen($this->userToken);
        } catch (InvalidTokenException $exception) {
            $apiLimitReached = true;
        }

        if ($apiLimitReached && $findNextAvailableToken) {
            $token = $this->tokenRepository->findFirstUnfrozenToken();
            $unfrozenToken = $token !== null;

            while ($apiLimitReached && $unfrozenToken) {
                $apiLimitReached = !$this->isApiAvailableForToken($endpoint, $token);
                $token = $this->tokenRepository->findFirstUnfrozenToken();
                $unfrozenToken = $token !== null;
            }
        }

        if ($apiLimitReached) {
            $message = $this->translator->trans('twitter.error.api_limit_reached.all_tokens', [], 'messages');
            $this->logger->info($message);

            if (!isset($token)) {
                $token = $this->tokenRepository->findFirstFrozenToken();
            }

            $this->waitUntilTokenUnfrozen($token);
        }

        return $token;
    }

    /**
     * @param $endpoint
     * @param Token $token
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @param $endpoint
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function isApiAvailable($endpoint)
    {
        $availableApi = false;

        try {
            if (!$this->isApiRateLimitReached($endpoint)) {
                $availableApi = true;
            }
        } catch (\Exception $exception) {
            if ($exception->getCode() === $this->getEmptyReplyErrorCode()) {
                $availableApi = true;
            } else {
                $this->logger->error($exception->getMessage());
                $this->tokenRepository->freezeToken($this->userToken);
            }
        }

        return $availableApi;
    }

    /**
     * @param Token $token
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
     * @param Token $token
     * @return string
     */
    protected function takeFirstTokenCharacters(Token $token)
    {
        return substr($token->getOauthToken(), 0, '8');
    }

    /**
     * @param $exception
     * @return \stdClass
     */
    private function convertExceptionIntoContent($exception): \stdClass
    {
        return (object) [
            'errors' => [
                (object)[
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ],
            ],
        ];
    }

    /**
     * @param $endpoint
     * @return mixed|object|\stdClass
     * @throws \Exception
     */
    private function fetchContent($endpoint)
    {
        if ($this->shouldUseBearerToken()) {
            $this->setupClient();

            return $this->contactEndpointUsingBearerToken($endpoint);
        }

        $tokens = $this->getTokens();
        $token = $this->preEndpointContact($tokens, $endpoint);

        return $this->contactEndpointUsingConsumerKey($endpoint, $token, $tokens);
    }

    /**
     * @param            $endpoint
     * @param Token|null $token
     * @return Token
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function maybeGetToken($endpoint, Token $token = null): Token
    {
        if (is_null($token)) {
            $tokens = $this->getTokens();

            return $this->preEndpointContact($tokens, $endpoint);
        }

        return $token;
    }

    /**
     * @param $screenName
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
     * @param $screenName
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
     * @throws NotFoundMemberException
     * @throws SuspendedAccountException
     */
    private function guardAgainstSpecialMembers($screenName): void
    {
        $member = $this->userRepository->findOneBy(['twitter_username' => $screenName]);
        if ($member instanceof User) {
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
     * @param int $identifier
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     */
    private function guardAgainstSpecialMemberWithIdentifier(int $identifier): void
    {
        $member = $this->userRepository->findOneBy(['twitterID' => $identifier]);
        if ($member instanceof User) {
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
     * @param string   $endpoint
     * @param callable $fetchContent
     * @return null
     */
    private function fetchContentWithRetries(string $endpoint, callable $fetchContent)
    {
        $content = null;

        $this->logger->info(sprintf('About to fetch content by making contact with endpoint "%s"', $endpoint));

        $retries = 0;
        while ($retries < self::MAX_RETRIES + 1) {
            try {
                $content = $fetchContent($endpoint);
                $this->guardAgainstContentFetchingException($content, $endpoint);

                break;
            } catch (OverCapacityException $exception) {
                $this->logger->info(sprintf(
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
     * @param $content
     * @param $endpoint
     * @throws ApiRateLimitingException
     * @throws NotFoundStatusException
     * @throws OverCapacityException
     */
    private function guardAgainstContentFetchingException($content, $endpoint): void
    {
        if ($this->hasError($content)) {
            if (isset($content->error)) {
                if ($content->error === 'Read-only application cannot POST.') {
                    throw new ReadOnlyApplicationException($content->error);
                }

                throw new \Exception($content->error);
            }

            $errorCode = $content->errors[0]->code;

            if ($errorCode === self::ERROR_OVER_CAPACITY) {
                throw new OverCapacityException(
                    $content->errors[0]->message,
                    $content->errors[0]->code
                );
            }

            if ($errorCode === self::ERROR_NO_STATUS_FOUND_WITH_THAT_ID) {
                throw new NotFoundStatusException(
                    $content->errors[0]->message,
                    $content->errors[0]->code
                );
            }

            if ($errorCode === self::ERROR_EXCEEDED_RATE_LIMIT) {
                $this->delayUnknownExceptionHandlingOnEndpointForToken($endpoint);
                throw new ApiRateLimitingException(
                    $content->errors[0]->message,
                    $content->errors[0]->code
                );
            }

            if ($errorCode === self::ERROR_USER_NOT_FOUND ||
                $errorCode === self::ERROR_CAN_NOT_FIND_SPECIFIED_USER ||
                $errorCode === self::ERROR_NOT_FOUND) {
                throw new NotFoundMemberException(
                    $content->errors[0]->message,
                    $content->errors[0]->code
                );
            }

            if ($errorCode === self::ERROR_SUSPENDED_USER) {
                throw new SuspendedAccountException(
                    $content->errors[0]->message,
                    $content->errors[0]->code
                );
            }

            if (isset($content->error) && ($content->error === 'Not authorized.')) {
                throw new ProtectedAccountException(
                    $content->error,
                    self::ERROR_PROTECTED_TWEET
                );
            }
        }
    }

    /**
     * @param string $memberName
     * @return \API|mixed|null|object|\stdClass|User
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function ensureMemberHavingNameExists(string $memberName)
    {
        return $this->statusAccessor->ensureMemberHavingNameExists($memberName);
    }

    /**
     * @param int $memberId
     * @return MemberInterface|null|object
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function ensureMemberHavingIdExists(int $memberId)
    {
        return $this->statusAccessor->ensureMemberHavingIdExists($memberId);
    }
}

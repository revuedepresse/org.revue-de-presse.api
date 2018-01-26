<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Api;

use Doctrine\Common\Persistence\ObjectRepository;

use GuzzleHttp\RequestOptions;

use Psr\Log\LoggerInterface;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException,
    WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Api
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Accessor implements TwitterErrorAwareInterface
{
    private $statusEndpoint = '/statuses/show.json?id={{ tweet_id }}';

    /**
     * @var
     */
    public $userToken;

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

    public function getApiHost()
    {
        return $this->apiHost;
    }

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Moderator\ApiLimitModerator $moderator
     */
    protected $moderator;

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
     * @var \Symfony\Component\Translation\Translator $translator
     */
    protected $translator;

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
     * Gets user secret
     *
     * @return mixed
     */
    public function getUserSecret()
    {
        return $this->userSecret;
    }

    /**
     * Gets user token
     *
     * @return mixed
     */
    public function getUserToken()
    {
        return $this->userToken;
    }

    /**
     * @param $consumerKey
     * @return $this
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    /**
     * @param $consumerSecret
     * @return $this
     */
    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    /**
     * Sets user secret
     *
     * @param $userSecret
     * @return $this
     */
    public function setUserSecret($userSecret)
    {
        $this->userSecret = $userSecret;

        return $this;
    }

    /**
     * Sets user token
     *
     * @param $userToken
     * @return $this
     */
    public function setUserToken($userToken)
    {
        $this->userToken = $userToken;

        return $this;
    }

    /**
     * Sets authentication header
     *
     * @param $header
     * @return $this
     */
    public function setAuthenticationHeader($header)
    {
        $this->authenticationHeader = $header;

        return $this;
    }

    /**
     * @param $host
     * @return $this
     */
    public function setApiHost($host)
    {
        $this->apiHost = $host;

        return $this;
    }

    /**
     * @param $clientClass
     * @return $this
     */
    public function setClientClass($clientClass)
    {
        $this->clientClass = $clientClass;

        return $this;
    }

    /**
     * @param $httpClientClass
     * @return $this
     */
    public function setHttpClientClass($httpClientClass)
    {
        $this->httpClientClass = $httpClientClass;

        return $this;
    }

    /**
     * @param $tokenRepository
     * @return $this
     */
    public function setTokenRepository(ObjectRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

    /**
     * Fetch user timeline statuses
     *
     * @param $options
     * @return \API|mixed|object
     * @throws \Exception
     */
    public function fetchTimelineStatuses($options){
        return $this->fetchTimeline(
            function () {
                return $this->getUserTimelineStatusesEndpoint();
            },
            $options
        );
   	}

   	/**
     * Fetch home timeline statuses
     *
     * @param $options
     * @return \API|mixed|object
     * @throws \Exception
     */
    public function fetchHomeTimelineStatuses($options){
        return $this->fetchTimeline(
            function () {
                return $this->getHomeTimelineStatusesEndpoint();
            },
            $options
        );
   	}

    /**
     * Fetch timeline
     *
     * @param callable $endpointGetter
     * @param array     $options
     * @return \API|mixed|object
     * @throws \Exception
     */
    public function fetchTimeline(callable $endpointGetter, $options){
   		if (is_null($options) || (!is_object($options) && !is_array($options))) {
            throw new \Exception('Invalid options');
        } else {
            if (is_array($options)) {
                $options = (object) $options;
            }

            $parameters = $this->validateRequestOptions($options);

            if (!array_key_exists('trim_user', $parameters)) {
                $parameters['trim_user'] = 0;
            }

            if (!array_key_exists('include_entities', $parameters)) {
                $parameters['include_entities'] = 1;
            }

            if (!array_key_exists('exclude_replies', $parameters)) {
                $parameters['exclude_replies'] = 0;
            }

            $endpoint = $endpointGetter(). '?'.implode('&', $parameters);

            return $this->contactEndpoint($endpoint);
        }
   	}

    /**
     * @param string $version
     * @return string
     */
    protected function getUserTimelineStatusesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/user_timeline.json';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getHomeTimelineStatusesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/home_timeline.json';
    }

    /**
     * @return mixed
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
        return $this->getApiBaseUrl($version) . '/application/rate_limit_status.json?resources=statuses';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getApiBaseUrl($version = '1.1') {
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
        $validatedOptions[] = 'count=' . intval($resultsCount);

        if (isset($options->{'max_id'})) {
            $maxId = $options->{'max_id'};
            $validatedOptions[] = 'max_id=' . intval($maxId);
        }

        if (isset($options->{'screen_name'})) {
            $screenName = $options->{'screen_name'};
            $validatedOptions[] = 'screen_name=' . $screenName;
        }

        if (isset($options->{'since_id'})) {
            $sinceId = $options->{'since_id'};
            $validatedOptions[] = 'since_id=' . $sinceId;
        }

        if (isset($options->{'include_rts'})) {
            $includeRetweets = $options->{'include_rts'};
            $validatedOptions[] = 'include_rts=' . intval($includeRetweets);
        }

        if (isset($options->{'exclude_replies'})) {
            $excludeReplies = $options->{'exclude_replies'};
            $validatedOptions[] = 'exclude_replies=' . intval($excludeReplies);
        }

        if (isset($options->{'trim_user'})) {
            $trimUser = $options->{'trim_user'};
            $validatedOptions[] = 'trim_user=' . intval($trimUser);
        }

        return $validatedOptions;
    }

    /**
     * @param $endpoint
     * @return \API|mixed|object
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function contactEndpoint($endpoint)
    {
        $tokens = $this->getTokens();
        $httpClient = $this->httpClient;

        $token = $this->beforeMakingContactWithApi($tokens);

        try {
            if (is_null($this->authenticationHeader)) {
                /** @var \TwitterOAuth $connection */
                $connection = new $httpClient(
                    $tokens['key'],
                    $tokens['secret'],
                    $tokens['oauth'],
                    $tokens['oauth_secret']
                );
                $connection->timeout = 3600;
                $connection->connecttimeout = 3600;

                $content = $connection->get($endpoint, []);
            } else {
                $this->setupClient();

                $httpClient->setHeader('Authorization', $this->authenticationHeader);
                $httpClient->request('GET', $endpoint);

                /** @var \Symfony\Component\HttpFoundation\Response $response */
                $response = $httpClient->getResponse();
                $encodedContent = $response->getContent();
                $content = json_decode($encodedContent);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
            $content = (object)['errors' => [(object)[
                'message' => $exception->getMessage(),
                'code' => $exception->getCode()
            ]]];
        }

        return $this->afterMakingContactWithApi($endpoint, $content, $token);
    }

    /**
     * @param $errorMessage
     * @param $errorCode
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    protected function throwException($errorMessage, $errorCode)
    {
        if ($errorCode === self::ERROR_SUSPENDED_USER) {
            throw new SuspendedAccountException($errorMessage, $errorCode);
        } else {
            throw new UnavailableResourceException($errorMessage, $errorCode);
        }
    }

    public function setupClient()
    {
        $clientClass = $this->clientClass;
        $this->httpClient = new $clientClass();

        $httpClientClass = $this->httpClientClass;
        $httpClient = new $httpClientClass([
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => 3600*60
        ]);

        $this->httpClient->setClient($httpClient);
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
                'secret' => $this->consumerSecret
            ];
        } else {
            throw new \Exception('Invalid tokens');
        }

        return $tokens;
    }

    /**
     * @param $identifier
     * @return \API|mixed|object
     */
    public function showUser($identifier)
    {
        $screenName = null;
        $userId = null;

        if (is_integer($identifier)) {
            $userId = $identifier;
            $option = 'user_id';
        } else {
            $screenName = $identifier;
            $option = 'screen_name';
        }
        $showUserEndpoint = $this->getShowUserEndpoint($version = '1.1', $option);

        return $this->contactEndpoint(strtr($showUserEndpoint, [
            '{{ screen_name }}' => $screenName, '{{ user_id }}' => $userId
        ]));
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
     * @param $screenName
     * @return \API|mixed|object
     */
    public function showUserFriends($screenName)
    {
        $showUserFriendEndpoint = $this->getShowUserFriendsEndpoint();

        return $this->contactEndpoint(str_replace('{{ screen_name }}', $screenName, $showUserFriendEndpoint));
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getShowUserFriendsEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/friends/ids.json?screen_name={{ screen_name }}';
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getShowStatusEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/show.json?id={{ id }}';
    }

    /**
     * @param $identifier
     * @return \API|mixed|object
     * @throws \InvalidArgumentException
     */
    public function showStatus($identifier)
    {
        if (!is_numeric($identifier)) {
            throw new \InvalidArgumentException('A status identifier should be an integer');
        }
        $showStatusEndpoint = $this->getShowStatusEndpoint($version = '1.1');

        return $this->contactEndpoint(strtr($showStatusEndpoint, ['{{ id }}' => $identifier]));
    }

    /**
     * @param $screenName
     * @return \API|mixed|object
     */
    public function getUserLists($screenName)
    {
        return $this->contactEndpoint(strtr($this->getUserListsEndpoint(),
            [
                '{{ screenName }}'  => $screenName,
                '{{ reverse }}'     => true,
            ]
        ));
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
     * @param $id
     * @return \API|mixed|object
     */
    public function getListMembers($id)
    {
        return $this->contactEndpoint(strtr($this->getListMembersEndpoint(), ['{{ id }}' => $id]));
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getListMembersEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/lists/members.json?list_id={{ id }}';
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
            $leastUpperBound = $rateLimitStatus->resources->statuses->$endpoint->limit;
            $remainingCalls = $rateLimitStatus->resources->statuses->$endpoint->remaining;
            $remainingCallsMessage = $this->translator->transChoice(
                'logs.info.calls_remaining',
                $remainingCalls,
                [
                    '{{ count }}' => $remainingCalls,
                    '{{ endpoint }}' => $endpoint,
                    '{{ identifier }}' => substr($this->userToken, 0, '8'),
                ],
                'logs'
            );
            $this->logger->info($remainingCallsMessage);

            return $remainingCalls < floor($leastUpperBound * (1/10));
        }
    }

    /**
     * @param $response
     * @return bool
     */
    protected function hasError($response)
    {
        return is_object($response) &&
            isset($response->errors) &&
            is_array($response->errors) &&
            isset($response->errors[0]);
    }

    /**
     * @param string $version
     * @return string
     */
    protected function getShowTweetEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version).$this->statusEndpoint;
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function getTweet($id)
    {
        $showTweetEndpoint = $this->getShowTweetEndpoint();

        return $this->contactEndpoint(strtr($showTweetEndpoint, ['{{ tweet_id }}' => $id]));
    }

    /**
     * @param $tokens
     * @return \WeavingTheWeb\Bundle\ApiBundle\Entity\Token
     */
    private function beforeMakingContactWithApi($tokens)
    {
        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token */
        $token = $this->tokenRepository->refreshFreezeCondition($tokens['oauth'], $this->logger);
        if ($token->isFrozen()) {
            $now = new \DateTime;
            $this->moderator->waitFor(
                $token->getFrozenUntil()->getTimestamp() - $now->getTimestamp(),
                [
                    '{{ token }}' => substr($token->getOauthToken(), 0, '8'),
                ]
            );
        }

        return $token;
    }

    /**
     * @param $endpoint
     * @param $content
     * @param $token
     * @return \API|array|mixed|object
     */
    private function afterMakingContactWithApi($endpoint, $content, $token)
    {
        $this->logger->info('[info] ' . $endpoint);

        if (!$this->hasError($content)) {
            return $content;
        }

        $errorMessage = $content->errors[0]->message;
        $errorCode = $content->errors[0]->code;

        $this->logger->error('[message] ' . $errorMessage);
        $this->logger->error('[code] ' . $errorCode);
        $this->logger->error('[token] ' . $token->getOauthToken());

        $reflection = new \ReflectionClass(__NAMESPACE__ . '\TwitterErrorAwareInterface');
        $errorCodes = $reflection->getConstants();

        if ($errorCode === self::ERROR_NO_DATA_AVAILABLE_FOR_SPECIFIED_ID) {
            return ['error' => $errorMessage];
        }

        if (in_array($errorCode, $errorCodes)) {
            if ($errorCode == self::ERROR_EXCEEDED_RATE_LIMIT) {
                $this->tokenRepository->freezeToken($token->getOauthToken());
            }
            $this->throwException($errorMessage, $errorCode);
        }

        /** Freeze token and wait for 15 minutes before getting back to operation */
        $this->tokenRepository->freezeToken($token->getOauthToken());
        $this->moderator->waitFor(
            15 * 60,
            [
                '{{ token }}' => substr($token->getOauthToken(), 0, '8'),
            ]
        );

        return $this->contactEndpoint($endpoint);
    }
}

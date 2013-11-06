<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Api;

use Psr\Log\LoggerInterface;

/**
 * Class Accessor
 * @package WeavingTheWeb\Bundle\TwitterBundle\Api
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Accessor
{
    public $userToken;

    /**
     * @var \Goutte\Client $httpClient
     */
    public $httpClient;

    public $httpClientClass;

    public $clientClass;

    public $environment = 'dev';

    protected $apiHost = 'api.twitter.com';

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;

    protected $userSecret;

    protected $consumerKey;

    protected $consumerSecret;

    protected $authenticationHeader;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    public function setLogger($logger = null)
    {
        if (!is_null($logger)) {
            $this->logger = $logger;
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
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    /**
     * @param $consumerSecret
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
     */
    public function setUserToken($userToken)
    {
        $this->userToken = $userToken;

        return $this;
    }

    /**
     * Sets authentication header
     *
     * @param $userToken
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
   	* Fetch timeline statuses
   	*
   	* @param	mixed	$options
   	* @return	mixed
   	*/
   	public function fetchTimelineStatuses($options){
   		if (is_null($options) || (!is_object($options) && !is_array($options))) {
            throw new \Exception('Invalid options');
        } else {
            if (is_array($options)) {
                $options = (object) $options;
            }
            $parameters = $this->validateRequestOptions($options);
            $endpoint = $this->getUserTimelineStatusesEndpoint() . '&' . implode('&', $parameters);

            return $this->contactEndpoint($endpoint);
        }
   	}

    /**
     * @param string $version
     * @return string
     */
    protected function getUserTimelineStatusesEndpoint($version = '1.1')
    {
        return $this->getApiBaseUrl($version) . '/statuses/user_timeline.json?' .
            'include_entities=1&include_rts=1&exclude_replies=0&trim_user=0';
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

    public function contactEndpoint($endpoint)
    {
        $parameters = array();
        $response = null;

        $tokens = $this->getTokens();
        $httpClient = $this->httpClient;

        try {
            if (is_null($this->authenticationHeader)) {
                /**
                 * @var \TwitterOAuth $connection
                 */
                $connection = new $httpClient(
                    $tokens['key'],
                    $tokens['secret'],
                    $tokens['oauth'],
                    $tokens['oauth_secret']
                );

                $content = $connection->get($endpoint, $parameters);
            } else {
                $this->setupClient();
                $this->httpClient->setHeader('Authorization', $this->authenticationHeader);
                $this->httpClient->request('GET', $endpoint);

                /**
                 * @var \Symfony\Component\HttpFoundation\Response $response
                 */
                $response = $this->httpClient->getResponse();
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

        return $content;
    }

    public function setupClient()
    {
        $clientClass = $this->clientClass;
        $this->httpClient = new $clientClass();

        $httpClientClass = $this->httpClientClass;
        $httpClient = new $httpClientClass('', array(
            $httpClientClass::SSL_CERT_AUTHORITY => false,
            'curl.options' => array(CURLOPT_TIMEOUT => 1800)));

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
     * @param string $endpoint
     * @return bool
     */
    public function isApiRateLimitReached($endpoint = '/statuses/show/:id')
    {
        $rateLimitStatus = $this->fetchRateLimitStatus();

        if ($this->hasError($rateLimitStatus)) {
            $message = $rateLimitStatus->errors[0]->message;

            $this->logger->error($message);

            return $rateLimitStatus->errors[0]->code;
        } else {
            $leastUpperBound = $rateLimitStatus->resources->statuses->$endpoint->limit;
            $remainingCall = $rateLimitStatus->resources->statuses->$endpoint->remaining;
            $message = '[rate limit for "' . $endpoint . '"] ' . $remainingCall;

            $this->logger->info($message);

            return $remainingCall < floor($leastUpperBound * (1/10));
        }
    }

    /**
     * @param $response
     * @return bool
     */
    protected function hasError($response)
    {
        return isset($response->errors) && is_array($response->errors) && isset($response->errors[0]);
    }
}

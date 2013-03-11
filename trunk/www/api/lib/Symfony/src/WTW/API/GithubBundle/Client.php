<?php

namespace WTW\API\GithubBundle;

/**
 * WTW\API\GithubBundle\Client
 *
 * @@author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Client
{
    protected $activeLogging = false;

    protected $accessToken = null;

    protected $clientId = null;

    protected $clientSecret = null;

    protected $dumper = null;

    protected $jsonStore = null;

    protected $redirectUri = null;

    protected $siteMap = null;

    public function __construct($dumper = null)
    {
        if (!is_null($dumper)) {
            $this->dumper = $dumper;
        }
    }

    /**
     * Authorizes the client to access the API
     */
    public function authorize()
    {
        $siteMap  = $this->getSiteMap();
        $clientId = $this->getClientId();
        $endpoint = $siteMap['url']['main'] . $siteMap['uri']['authorize'];
        $scope    = $this->getAuthorizationScope();
        $state    = md5(time());

        $url =
            $endpoint . '?' .
                'client_id=' . $clientId . '&' .
                'redirect_uri=' . $this->getRedirect() . '&' .
                'scope=' . $scope . '&' .
                'state=' . $state;

        $_SESSION['api.github.state'] = $state;

        $this->log(
            __METHOD__,
            array('[url]', $url, '[state]', $state),
            true
        );
    }

    /**
     * @return null
     */
    public function disableLogging()
    {
        return $this->activeLogging = false;
    }

    /**
     * @return null
     */
    public function enableLogging()
    {
        return $this->activeLogging = true;
    }

    /**
     * @return null
     */
    protected function getAccessToken()
    {
        return $this->accessToken;
    }

    protected function getAuthorizationScope()
    {
        return 'user,public_repo,repo,delete_repo,gist';
    }

    /**
     * @return null
     */
    protected function getClientId()
    {
        return $this->accessToken;
    }

    /**
     * @return null
     */
    protected function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Gets Json Store
     *
     * @return mixed
     */
    public function getJsonStore()
    {
        return $this->jsonStore;
    }

    /**
     * Gets redirect URI
     *
     * @return mixed
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @return null
     */
    public function getSiteMap()
    {
        return $this->siteMap;
    }

    /**
     * Get token parameter
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getTokenParameter()
    {
        if ($this->getAccessToken() === null) {
            if (defined('API_GITHUB_TOKEN')) {
                $this->setAccessToken(API_GITHUB_TOKEN);
            } else {
                throw new \InvalidArgumentException('Invalid Token');
            }
        }

        return 'access_token=' . $this->getAccessToken();
    }

    /**
     * Crawls a URL
     *
     * @param      $url
     * @param bool $headers
     *
     * @return mixed
     * @throws \Exception
     */
    public function crawlUrl($url, $headers = false)
    {
        $resource = curl_init();

        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_URL, $url);
        curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, false);

        if ($headers) {
            curl_setopt($resource, CURLOPT_HEADER, true);
        }

        $response = curl_exec($resource);

        if ($error = curl_error($resource)) {
            throw new \Exception($error . ' (' . $url . ')');
        }
        curl_close($resource);

        return $response;
    }

    /**
     * Gets following
     *
     * @param array $extraParameters
     *
     * @return mixed
     */
    public function getFollowedUsers(array $extraParameters = [])
    {
        $uri = $this->getUserFollowingUri();
        $uri = $this->appendExtraParameters($uri, $extraParameters);
        $response = $this->crawlUrl($uri, $headers = true);

        $headerFields = $this->parseHeader($response);
        $response = $this->removeHeaders($response);

        if (isset($headerFields['Link'])) {
            $links = $this->parseLinks($headerFields);
            $nextPage = $this->hasNextPage($extraParameters, $links);

            if ($nextPage) {
                $followedUsers = $this->getFollowedUserByPage($links['next']['page']);
                $response = $this->mergeJsonResponses($response, $followedUsers);
            }
        }

        return $response;
    }

    /**
     * @param $page
     *
     * @return mixed
     */
    public function getFollowedUserByPage($page)
    {
        return $this->getFollowedUsers(['page' => $page]);
    }

    /**
     * @param $extraParameters
     * @param $links
     *
     * @return mixed
     */
    public function hasNextPage($extraParameters, $links)
    {
        return !isset($extraParameters['page']) ||
            isset($links['last']) &&
            $links['last']['page'] > $extraParameters['page'];
    }

    /**
     * @param $response
     * @param $followedUsers
     *
     * @return string|void
     */
    public function mergeJsonResponses($response, $followedUsers)
    {
        return json_encode(
            array_merge(
                json_decode($response, true),
                json_decode($followedUsers, true)
            )
        );
    }

    /**
     * @param $headerFields
     *
     * @return array
     */
    public function parseLinks($headerFields)
    {
        $parsingResults = [];
        $fieldValue = $headerFields['Link'];
        $links = explode(',', $fieldValue);
        $pattern  = '/\s?<(?<uri>[^>]+page=' .
            '(?<page>[0-9]+)[^>]+)>; ' .
            'rel="(?<rel>[^"]+)"/';

        foreach ($links as $link) {
            $match = preg_match($pattern, $link, $matches);

            if ($match) {
                $parsingResults[$matches['rel']] = array(
                    'uri'  => $matches['uri'],
                    'page' => $matches['page']
                );
            }
        }

        return $parsingResults;
    }

    /**
     * @return string
     */
    public function getUserFollowingUri()
    {
        $parameterToken = $this->getTokenParameter();
        $siteMap        = $this->getSiteMap();

        return $siteMap['url']['base'] . $siteMap['uri']['get_following'] .
            '?' . $parameterToken .
            '&' . 'redirect_uri=' . urlencode($this->getRedirect());
    }

    /**
     * @param       $uri
     * @param array $extraParameters
     *
     * @return string
     */
    public function appendExtraParameters($uri, array $extraParameters = [])
    {
        foreach ($extraParameters as $name => $value) {
            $uri .= '&' . $name . '=' . $value;
        }

        return $uri;
    }

    /**
     * @param $response
     *
     * @return array
     */
    public function parseHeader($response)
    {
        $headers = [];
        $match = preg_match('/(HTTP\/.+)\r\n\r\n/ms', $response, $matches);

        if ($match) {
            $fields = explode("\r\n", $matches[1]);
            unset($fields[0]); // Removes status line
            foreach ($fields as $field) {
                $delimiter = ': ';
                if (strpos($field, $delimiter) !== false) {
                    list($key, $value) = explode($delimiter, $field, 2);
                    $headers[$key] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    public function removeHeaders($response)
    {
        return preg_replace('/HTTP\/.+\r\n\r\n/ms', '', $response);
    }

    /**
     * Gets organization
     *
     * @return mixed
     */
    public function getOrganizations()
    {
        $siteMap        = $this->getSiteMap();
        $parameterToken = $this->getTokenParameter();

        $endpoint = $siteMap['url']['base'] . $siteMap['uri']['get_orgs'] .
            '?' . $parameterToken;
        $response = $this->crawlUrl($endpoint);

        $this->log(
            __METHOD__,
            array(
                '[repositories request]',
                $endpoint,
                '[repositories response]',
                $response
            ),
            true
        );

        return $response;
    }

    public function getRedirect()
    {
        return $this->getSiteMap()['url']['redirect'];
    }

    /**
     * Gets repositories
     *
     * @return mixed
     */
    public function getRepositories()
    {
        $siteMap        = $this->getSitemap();
        $parameterToken = $this->getTokenParameter();

        $endpoint = $siteMap['url']['base'] . $siteMap['uri']['get_repos'] .
            '?' . $parameterToken .
            '&' . 'redirect_uri=' . $this->getRedirect();
        $response = $this->crawlUrl($endpoint);

        $this->log(
            __METHOD__,
            array(
                '[repositories request]',
                $endpoint,
                '[repositories response]',
                $response
            ),
            true
        );

        return $response;
    }

    /**
     * Gets user starred repositories
     *
     * @param $user
     *
     * @return mixed
     */
    public function getStarredRepositories($user)
    {
        $siteMap        = $this->getSiteMap();
        $parameterToken = $this->getTokenParameter();
        $endpoint       = str_replace(':user', $user, $siteMap['uri']['get_starred']);

        $endpoint = $siteMap['url']['base'] . $endpoint .
            '?' . $parameterToken .
            '&' . 'redirect_uri=' . $this->getRedirect();
        $response = $this->crawlUrl($endpoint);

        $this->log(
            __METHOD__,
            array(
                '[endpoint]',
                $endpoint,
                '[response]',
                $response
            )
        );

        return $response;
    }

    /**
     * Gets user
     *
     * @return mixed
     */
    public function getUser()
    {
        $siteMap        = $this->getSiteMap();
        $parameterToken = $this->getTokenParameter();

        $endpoint = $siteMap['url']['base'] . $siteMap['uri']['get_user'] .
            '?' . $parameterToken;
        $response = $this->crawlUrl($endpoint);

        $this->log(
            __METHOD__,
            array(
                '[user request]',
                $endpoint,
                '[user response]',
                $response
            )
        );

        return $response;
    }

    public function isLoggingActive()
    {
        return $this->activeLogging;
    }

    public function log()
    {
        $callback = null;

        if ($this->isLoggingActive()) {
            $arguments = func_get_args();

            if (isset($this->dumper) && is_callable(array($this->dumper, __FUNCTION__))) {
                $callback = call_user_func_array(array($this->dumper, __FUNCTION__), $arguments);
            } else {
                error_log(print_r($arguments, true));
            }
        }

        return $callback;
    }

    /**
     * Opens a URL
     *
     * @param $url
     *
     * @return string
     */
    function openUrl($url)
    {
        $response = '';
        $handle   = fopen($url, 'r', false);

        if (is_resource($handle)) {
            while (!feof($handle)) {
                $response .= fread($handle, 8096);
            }
        }

        return $response;
    }

    /**
     * Request access token
     *
     * @param $code
     *
     * @return mixed
     */
    public function request($code)
    {
        $siteMap = $this->getSiteMap();

        $endpoint = $siteMap['url']['main'] . $siteMap['uri']['access_token'];

        $resource = curl_init();

        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_URL, $endpoint);

        $post =
            'code=' . $code . '&' .
                'client_id=' . $this->getClientId() . '&' .
                'client_secret=' . $this->getClientSecret() . '&' .
                'state=' . $_SESSION['api.github.state'];

        curl_setopt($resource, CURLOPT_POST, true);
        curl_setopt($resource, CURLOPT_POSTFIELDS, $post);
        curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($resource);

        $this->log(
            __METHOD__,
            array(
                '[endpoint]',
                $endpoint,
                '[POST parameters]',
                $post,
                '[received $_GET values]',
                $_GET,
                '[response]',
                $response
            ),
            true
        );

        curl_close($resource);

        return $response;
    }

    /**
     * Saves repositories
     *
     * @param $repositories
     */
    public function saveRepositories($repositories)
    {
        $jsonStore = $this->getJsonStore();

        $jsonStore->saveStarredRepositories($repositories);
    }

    /**
     * Sends request
     *
     * @param           $action
     * @param array     $arguments
     * @param boolean   $output
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function sendRequest($action, $arguments = array(), $output = true)
    {
        if (in_array($action, get_class_methods(__CLASS__))) {
            $response = call_user_func_array(array($this, $action), $arguments);
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid action (%s)', $action));
        }

        if ($output) {
            header('Content-type: application/javascript');
            echo $response;
        }

        return $response;
    }

    /**
     * @param $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @param $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * Sets redirect Uri
     *
     * @param $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * Sets Json Store
     *
     * @param $store
     */
    public function setJsonStore($store)
    {
        $this->jsonStore = $store;
    }

    /**
     * @param $siteMap
     */
    public function setSiteMap($siteMap)
    {
        $this->siteMap = $siteMap;
    }
}

<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Security;

use GuzzleHttp\RequestOptions;

use Symfony\Component\BrowserKit\Response;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Security
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ApplicationAuthenticator
{
    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessor
     */
    public $accessor;

    /**
     * @var \Goutte\Client $client
     */
    protected $client;

    /**
     * @var string $key
     */
    public $key;

    /**
     * @var string $secret
     */
    public $secret;

    public $apiHost;

    public $authenticationUri;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    public $tokenRepository;

    protected function getApiBaseUrl()
    {
        return 'https://' . $this->apiHost;
    }

    public function setupClient($clientClass, $httpClientClass)
    {
        if (is_null($this->client)) {
            $this->client = new $clientClass();

            /**
             * @var \GuzzleHttp\Client $httpClientClass
             */
            $httpClient = new $httpClientClass([
                RequestOptions::VERIFY => false,
                RequestOptions::TIMEOUT => 1800
            ]);

            $this->client->setClient($httpClient);
        }
    }

    /**
     * @param $token
     * @param $secret
     * @return string
     */
    public function makeAuthorizationBasic($token, $secret)
    {
        return base64_encode(urlencode($token) . ':' . urlencode($secret));
    }

    /**
     * @param $basic
     * @return mixed
     * @throws \Exception
     */
    public function postOauth2Token($basic)
    {
        $rquestBody = 'grant_type=client_credentials';
        $this->client->setHeader('Authorization', 'Basic ' . $basic);
        $this->client->setHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
        $this->client->request('POST', $this->getApiBaseUrl() . '/' . $this->authenticationUri, [], [], [], $rquestBody);

        /**
         * @var $response Response
         */
        $response = $this->client->getResponse();
        $decodedResponse = json_decode($response->getContent(), true);
        $lastError = json_last_error();

        if ($lastError !== JSON_ERROR_NONE) {
            throw new \Exception('An error occurred when decoding the response (Error code: ' . $lastError . ')');
        }

        return $decodedResponse;
    }

    /**
     * @return object
     */
    public function authenticate($key = null, $secret = null)
    {
        if (!is_null($key) && !is_null($secret)) {
            $this->key = $key;
            $this->secret = $secret;
        }
        $basic = $this->makeAuthorizationBasic($this->key, $this->secret);

        $token = $this->tokenRepository->findOneBy(['oauthToken' => $this->key]);

        if (is_null($token)) {
            $response = $this->postOauth2Token($basic);
            if (array_key_exists('errors', $response)) {
                throw new \Exception($response['errors'][0]['message']);
            } else {
                $token = $this->tokenRepository->persistBearerToken($this->key, $response['access_token']);
            }
        }

        return ['consumer_key' => $this->key, 'access_token' => $token->getOauthTokenSecret()];
    }
}

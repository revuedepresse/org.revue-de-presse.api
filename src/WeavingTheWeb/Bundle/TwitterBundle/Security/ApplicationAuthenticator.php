<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Security;

use Symfony\Component\BrowserKit\Response;

/**
 * Class ApplicationAuthenticator
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

    public $apiHost;

    public $authenticationUri;

    protected function getApiBaseUrl()
    {
        return 'https://' . $this->apiHost;
    }

    public function setupClient($clientClass, $httpClientClass)
    {
        if (is_null($this->client)) {
            $this->client = new $clientClass();

            /**
             * @var \Guzzle\Http\Client $httpClientClass
             */
            $httpClient = new $httpClientClass('', array(
                $httpClientClass::SSL_CERT_AUTHORITY => false,
                'curl.options' => array(CURLOPT_TIMEOUT => 1800)));

            $this->client->setClient($httpClient);
        }
    }

    /**
     * @param $token
     * @param $secret
     */
    public function makeAuthorizationBasic($token, $secret)
    {
        return base64_encode(urlencode($token) . ':' . urlencode($secret));
    }

    /**
     *
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

        return $response->getContent();
    }
}
<?php

namespace WeavingTheWeb\Bundle\DataMiningBundle;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Miner
{
    /**
     * @var $client \Goutte\Client
     */
    protected $client;

    protected $endpoint;

    /**
     * @var string
     */
    protected $clientClass;

    /**
     * @var string
     */
    protected $httpClientClass;

    /**
     * Constructs a miner
     *
     * @param $endpoint
     */
    public function __construct($endpoint = null)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param string $clientClass
     */
    public function setClientClass($clientClass)
    {
        $this->clientClass = $clientClass;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param string $httpClientClass
     */
    public function setHttpClientClass($httpClientClass)
    {
        $this->httpClientClass = $httpClientClass;
    }

    /**
     * @param \Goutte\Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     *
     */
    public function setupClient()
    {
        if (is_null($this->client)) {
            $clientClass = $this->clientClass;
            $this->client = new $clientClass();

            $httpClientClass = $this->httpClientClass;
            $httpClient = new $httpClientClass('', array(
                $httpClientClass::SSL_CERT_AUTHORITY => false,
                'curl.options' => array(CURLOPT_TIMEOUT => 1800)));

            $this->client->setClient($httpClient);
        }
    }

    /**
     * Gets feed
     *
     * @return string
     * @throws \Exception
     */
    public function getFeed()
    {
        $this->setupClient();

        $this->client->request('GET', $this->endpoint);
        $response = $this->client->getResponse();
        $statusCode = $response->getStatus();

        if ($statusCode !== 200) {
            $content = $response->getContent();
            if (!empty($content)) {
                $message = $content;
            } else {
                $message = sprintf('Empty response content with status code %d', $statusCode);
            }

            throw new \Exception($message);
        }

        return $response->getContent();
    }

    /**
     * Sets a query string
     *
     * @param $queryString
     */
    public function setQueryString($queryString)
    {
        $this->endpoint .= $queryString;
    }
}

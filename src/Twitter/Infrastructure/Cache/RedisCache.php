<?php

namespace App\Twitter\Infrastructure\Cache;

use Predis\Client;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisCache
{
    /**
     * @var Client
     */
    private $client;

    public function __construct($host, $port) {
        $this->client = RedisAdapter::createConnection(sprintf(
        'redis://%s:%s',
            $host,
            $port
        ));
    }

    public function getClient()
    {
        return $this->client;
    }
}

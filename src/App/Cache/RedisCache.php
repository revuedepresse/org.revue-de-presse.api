<?php

namespace App\Cache;

use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisCache
{
    /**
     * @var \Predis\Client|\Redis
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

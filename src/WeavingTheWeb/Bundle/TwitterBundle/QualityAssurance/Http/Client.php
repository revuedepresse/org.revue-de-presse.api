<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\QualityAssurance\Http;

use FOS\ElasticaBundle\Client as BaseClient;

/**
 * Class Client
 * @package WeavingTheWeb\Bundle\TwitterBundle\Http
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Client extends BaseClient
{
    public function request($path, $method, $data = array(), array $query = array())
    {
        return;
    }
}
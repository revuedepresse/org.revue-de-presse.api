<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\QualityAssurance\Http;

use FOS\ElasticaBundle\Client as BaseClient;
use Elastica\Request;

/**
 * Class Client
 * @package WeavingTheWeb\Bundle\TwitterBundle\Http
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class Client extends BaseClient
{
    public function request($path, $method = Request::GET, $data = array(), array $query = array())
    {
        return;
    }
}
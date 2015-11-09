<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Repository\OAuth;

use Doctrine\ORM\EntityRepository;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth\Client;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ClientRepository extends EntityRepository
{
    /**
     * @param $clientId
     * @param $clientSecret
     * @param $redirectUri
     * @return Client
     */
    public function make($clientId, $clientSecret, $redirectUri)
    {
        return new Client($clientId, $clientSecret, $redirectUri);
    }
}

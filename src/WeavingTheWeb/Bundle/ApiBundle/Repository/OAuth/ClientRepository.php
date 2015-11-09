<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository\OAuth;

use Doctrine\ORM\EntityRepository;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ClientRepository extends EntityRepository
{
    /**
     * @var \FOS\OAuthServerBundle\Entity\ClientManager
     */
    public $clientManager;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    public $router;

    /**
     * @param $redirectUrl
     * @return \WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth\Client
     */
    public function make($redirectUrl)
    {
        /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth\Client $client */
        $client = $this->clientManager->createClient();

        $client->setRedirectUris(array($redirectUrl));
        $client->setAllowedGrantTypes(array('token', 'authorization_code'));

        $this->clientManager->updateClient($client);

        $authorizationUrl = $this->router->generate(
            'fos_oauth_server_authorize',
            [
                'redirect_uri' => $redirectUrl,
                'client_id' => $client->getPublicId(),
                'response_type' => 'code',
                'code' => ''
            ]
        );
        $client->setAuthorizationUrl($authorizationUrl);

        return $client;
    }
}

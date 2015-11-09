<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\OAuth\ClientRepository")
 * @ORM\Table(name="oauth_client")
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public $authorizationUrl;

    /**
     * @param $authorizationUrl
     * @return $this
     */
    public function setAuthorizationUrl($authorizationUrl)
    {
        $this->authorizationUrl = $authorizationUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAuthorizationUrl()
    {
        return $this->authorizationUrl;
    }

    public function __toString()
    {
        return $this->getPublicId();
    }
}

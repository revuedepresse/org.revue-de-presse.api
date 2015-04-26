<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @ORM\Entity
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

    public function __construct()
    {
        parent::__construct();
    }

    public function __toString()
    {
        return $this->getPublicId();
    }
}
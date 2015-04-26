<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;

use FOS\OAuthServerBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @ORM\Entity
 * @ORM\Table(name="oauth_refresh_token")
 */
class RefreshToken extends BaseRefreshToken
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client", referencedColumnName="id", nullable=false)
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="\WTW\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="usr_id")
     */
    protected $user;
}

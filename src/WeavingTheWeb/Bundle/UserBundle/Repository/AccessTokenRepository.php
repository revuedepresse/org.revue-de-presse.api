<?php

namespace WeavingTheWeb\Bundle\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

use WeavingTheWeb\Bundle\UserBundle\Entity\AccessToken;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AccessTokenRepository extends EntityRepository
{
    /**
     * @param $accessToken
     * @return AccessToken
     */
    public function make($accessToken)
    {
        $accessToken = new AccessToken($accessToken);
        $accessToken->setCreatedAt(new \DateTime());

        return $accessToken;
    }
}
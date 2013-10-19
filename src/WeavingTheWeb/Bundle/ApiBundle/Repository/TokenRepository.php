<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use Psr\Log\LoggerInterface;

/**
 * Class TokenRepository
 * @package WeavingTheWeb\Bundle\UserBundle\Repository
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class TokenRepository extends EntityRepository
{
    /**
     * @param $properties
     * @return Token
     */
    public function makeToken($properties)
    {
        $token = new Token();

        $now = new \DateTime();
        $token->setCreatedAt($now);
        $token->setUpdatedAt($now);

        $token->setOauthToken($properties['oauth_token']);
        $token->setOauthTokenSecret($properties['oauth_token_secret']);

        return $token;
    }

    /**
     * @param $token
     * @param \Psr\Log\LoggerInterface $logger
     * @return bool
     */
    public function isSerializationLocked($token, LoggerInterface $logger = null)
    {
        $locked = false;
        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token
         */
        $token = $this->findOneBy(['oauthToken' => $token]);
        if (!is_null($token) && !is_null($token->getFrozenUntil())) {
            $now = new \DateTime();

            $waitForNextWindow = $token->getFrozenUntil()->getTimestamp() > $now->getTimestamp();
            if ($waitForNextWindow) {
                $waitTime = $token->getFrozenUntil()->getTimestamp() - $now->getTimestamp();
                $locked = true;

                if (!is_null($logger)) {
                    if ($waitTime < 60) {
                        $humanWaitTime = $waitTime . ' more seconds';
                    } else {
                        $humanWaitTime = floor($waitTime / 60) . ' more minutes';
                    }

                    $logger->info(
                        'API limit has been reached for token "' . substr($token, 0, '8') . '...' . '", ' .
                        'operations are currently frozen (waiting for ' . $humanWaitTime . ')'
                    );
                }
                sleep($waitTime);
            }
        }

        return $locked;
    }

    /**
     * @param $oauthToken
     */
    public function freezeToken($oauthToken)
    {
        $entityManager = $this->getEntityManager();

        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token
         */
        $token = $this->findOneBy(['oauthToken' => $oauthToken]);
        $token->setFrozenUntil(new \DateTime('now + 15min'));

        $entityManager->persist($token);
        $entityManager->flush($token);
    }
}
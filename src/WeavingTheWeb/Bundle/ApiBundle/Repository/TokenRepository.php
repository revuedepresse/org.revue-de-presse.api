<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use Psr\Log\LoggerInterface;
use WeavingTheWeb\Bundle\ApiBundle\Entity\TokenType;

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
     * @param $oauthToken
     * @param string $until
     */
    public function freezeToken($oauthToken, $until = 'now + 15min')
    {
        $entityManager = $this->getEntityManager();

        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token
         */
        $token = $this->findOneBy(['oauthToken' => $oauthToken]);
        $token->setFrozenUntil(new \DateTime($until));

        $entityManager->persist($token);
        $entityManager->flush($token);
    }

    /**
     * @param $oauthToken
     * @param LoggerInterface $logger
     * @return bool
     */
    public function refreshFreezeCondition($oauthToken, LoggerInterface $logger = null)
    {
        $frozen = false;

        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token
         */
        $token = $this->findOneBy(['oauthToken' => $oauthToken]);

        if (is_null($token)) {
            $token = $this->makeToken(['oauth_token' => $oauthToken, 'oauth_token_secret' => null]);

            $entityManager = $this->getEntityManager();
            $entityManager->persist($token);
            $entityManager->flush();

            $logger->info('[token creation] ' . $token->getOauthToken());
        } elseif (
            !is_null($token->getFrozenUntil()) &&
            $token->getFrozenUntil()->getTimestamp() > (new \DateTime())->getTimestamp()
        ) {
            /**
             * The token is frozen if the "frozen until" date is in the future
             */
            $frozen = true;
        }
        /**
         *  else {
         *      The token was frozen but not anymore as "frozen until" date is now in the past
         *  }
         */

        $token->frozen = $frozen;

        return $token;
    }

    /**
     * @param $applicationToken
     * @param $accessToken
     * @return Token
     */
    public function persistBearerToken($applicationToken, $accessToken)
    {
        $tokenRepository = $this->getEntityManager()->getRepository('WeavingTheWebApiBundle:TokenType');
        $tokenType = $tokenRepository->findOneBy(['name' => TokenType::APPLICATION]);

        $token = new Token();
        $token->setOauthToken($applicationToken);
        $token->setOauthTokenSecret($accessToken);
        $token->setType($tokenType);
        $token->setCreatedAt(new \DateTime());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($token);
        $entityManager->flush();

        return $token;
    }
}
<?php
declare(strict_types=1);

namespace App\Api\AccessToken\Repository;

use App\Api\Entity\TokenInterface;
use App\Api\Exception\UnavailableTokenException;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NoResultException;

use Exception;
use Psr\Log\LoggerInterface;

use App\Api\Entity\Token,
    App\Api\Entity\TokenType;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @method TokenInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenInterface[]    findAll()
 * @method TokenInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenRepository extends ServiceEntityRepository implements TokenRepositoryInterface
{
    /**
     * @param $properties
     *
     * @return Token
     * @throws Exception
     */
    public function makeToken($properties): TokenInterface
    {
        $token = new Token();

        $now = new DateTime();
        $token->setCreatedAt($now);
        $token->setUpdatedAt($now);

        $tokenRepository = $this->getEntityManager()->getRepository('Api:TokenType');

        /** @var TokenType $tokenType */
        $tokenType = $tokenRepository->findOneBy(['name' => TokenType::USER]);
        $token->setType($tokenType);

        $token->setOauthToken($properties['oauth_token']);
        $token->setOauthTokenSecret($properties['oauth_token_secret']);

        return $token;
    }

    /**
     * @param        $oauthToken
     * @param string $until
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function freezeToken($oauthToken, $until = 'now + 15min')
    {
        $entityManager = $this->getEntityManager();

        /**
         * @var \App\Api\Entity\Token $token
         */
        $token = $this->findOneBy(['oauthToken' => $oauthToken]);
        $token->setFrozenUntil(new DateTime($until));

        $entityManager->persist($token);
        $entityManager->flush($token);
    }

    /**
     * @param string          $oauthToken
     * @param LoggerInterface $logger
     *
     * @return TokenInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function refreshFreezeCondition(
        string $oauthToken,
        LoggerInterface $logger
    ): TokenInterface {
        $frozen = false;

        $token = $this->findOneBy(['oauthToken' => $oauthToken]);

        if ($token === null) {
            $token = $this->makeToken(['oauth_token' => $oauthToken, 'oauth_token_secret' => '']);

            $entityManager = $this->getEntityManager();
            $entityManager->persist($token);
            $entityManager->flush();

            $logger->info('[token creation] ' . $token->getOauthToken());
        } elseif ($this->isTokenFrozen($token)) {
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

        $token->setFrozen($frozen);

        return $token;
    }

    /**
     * @param Token $token
     *
     * @return bool
     * @throws Exception
     */
    protected function isTokenFrozen(Token $token): bool
    {
        return $token->getFrozenUntil() !== null &&
            $token->getFrozenUntil()->getTimestamp() >
                (new DateTime('now', new DateTimeZone('UTC')))
                    ->getTimestamp();
    }

    /**
     * @param $oauthToken
     *
     * @return bool
     * @throws NonUniqueResultException
     */
    public function isOauthTokenFrozen($oauthToken): bool
    {
        $token = $this->findUnfrozenToken($oauthToken);

        return !($token instanceof Token);
    }

    /**
     * @param string $token
     *
     * @return Token|null
     * @throws NonUniqueResultException
     */
    public function findUnfrozenToken(string $token): ?Token
    {
        $queryBuilder = $this->createQueryBuilder('t');

        $queryBuilder->andWhere('t.oauthToken = :token');
        $queryBuilder->setParameter('token', $token);

        $this->applyUnfrozenTokenCriteria($queryBuilder);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            return null;
        }
    }

    /**
     * @return mixed|null
     * @throws NonUniqueResultException
     */
    public function howManyUnfrozenTokenAreThere(): int
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->select('COUNT(t.id) as count_');

        $this->applyUnfrozenTokenCriteria($this->createQueryBuilder('t'));

        try {
            return $queryBuilder->getQuery()->getSingleResult()['count_'];
        } catch (NoResultException $exception) {
            UnavailableTokenException::throws();
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    private function applyUnfrozenTokenCriteria(QueryBuilder $queryBuilder): QueryBuilder {
        $queryBuilder->andWhere('t.type = :type');
        $tokenRepository = $this->getEntityManager()->getRepository('Api:TokenType');
        $tokenType = $tokenRepository->findOneBy(['name' => TokenType::USER]);
        $queryBuilder->setParameter('type', $tokenType);

        $queryBuilder->andWhere('t.oauthTokenSecret IS NOT NULL');

        $queryBuilder->andWhere('(t.frozenUntil IS NULL or t.frozenUntil < NOW())');

        return $queryBuilder->setMaxResults(1);
    }

    /**
     * @param $applicationToken
     * @param $accessToken
     *
     * @return Token
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persistBearerToken($applicationToken, $accessToken)
    {
        $tokenRepository = $this->getEntityManager()->getRepository('Api:TokenType');
        $tokenType = $tokenRepository->findOneBy(['name' => TokenType::APPLICATION]);

        $token = new Token();
        $token->setOauthToken($applicationToken);
        $token->setOauthTokenSecret($accessToken);
        $token->setType($tokenType);
        $token->setCreatedAt(new DateTime());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($token);
        $entityManager->flush();

        return $token;
    }

    /**
     * @return mixed|null
     * @throws NonUniqueResultException
     */
    public function findFirstUnfrozenToken(): ?TokenInterface
    {
        $queryBuilder = $this->createQueryBuilder('t');

        $this->applyUnfrozenTokenCriteria($queryBuilder);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            return null;
        }
    }

    /**
     * @return mixed|null
     * @throws NonUniqueResultException
     */
    public function findFirstFrozenToken(): ?TokenInterface
    {
        $queryBuilder = $this->createQueryBuilder('t');

        $tokenRepository = $this->getEntityManager()->getRepository('Api:TokenType');
        $tokenType = $tokenRepository->findOneBy(['name' => TokenType::USER]);

        $queryBuilder->andWhere('t.type = :type');
        $queryBuilder->setParameter('type', $tokenType);

        $queryBuilder->andWhere('t.oauthTokenSecret IS NOT NULL');

        $queryBuilder->andWhere('t.frozenUntil > :now');
        $queryBuilder->setParameter(
            'now',
            new DateTime('now', new DateTimeZone('UTC'))
        );

        $queryBuilder->setMaxResults(1);
        $queryBuilder->orderBy('t.frozenUntil', 'ASC');

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            return null;
        }
    }

    /**
     * @param string $token
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findTokenOtherThan(string $token): ?TokenInterface
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->andWhere('t.oauthToken != :token');
        $queryBuilder->setParameter('token', $token);

        $queryBuilder->andWhere('t.frozenUntil < :now');
        $queryBuilder->setParameter('now', new DateTime('now', new DateTimeZone('UTC')));

        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getSingleResult();
    }
}

<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\AccessToken\Repository;

use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Entity\TokenType;
use App\Twitter\Infrastructure\Api\Exception\UnavailableTokenException;
use App\Twitter\Infrastructure\Database\Connection\ConnectionAwareInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Exception\InvalidTokensException;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Exception;

/**
 * @method TokenInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenInterface[]    findAll()
 * @method TokenInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenRepository extends ServiceEntityRepository implements TokenRepositoryInterface, ConnectionAwareInterface
{
    use LoggerTrait;

    /**
     * @param $properties
     *
     * @return Token
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

        // Ensure the newly created token is not frozen yet
        // equivalent to setting the frozen until date in the past
        $token->unfreeze();

        return $token;
    }

    public function ensureTokenExists(
        $oauthToken,
        $oauthTokenSecret,
        $consumerKey,
        $consumerSecret
    ): void {
        if ($this->findOneBy(['oauthToken' => $oauthToken]) !== null) {
            return;
        }

        $token = $this->makeToken([
            'oauth_token' => $oauthToken,
            'oauth_token_secret' => $oauthTokenSecret
        ]);
        $token->setConsumerKey($consumerKey);
        $token->setConsumerSecret($consumerSecret);

        $this->save($token);
    }

    public function freezeToken(TokenInterface $oauthToken): void
    {
        /** @var TokenInterface $token */
        $token = $this->findOneBy(['oauthToken' => $oauthToken->getOAuthToken()]);

        if (!($token instanceof TokenInterface)) {
            UnavailableTokenException::throws();
        }

        $token->freeze();

        $this->save($token);
    }

    public function findByUserToken(string $userToken): TokenInterface {
        $matchingTokens= $this->findBy(['oauthToken' => $userToken]);

        if (empty($matchingTokens)) {
            throw new UnavailableTokenException(
                sprintf(
                    'No token matching "%s" user token',
                    $userToken
                )
            );
        }

        return $matchingTokens[0];
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
     * @param Token $token
     *
     * @return bool
     * @throws Exception
     */
    protected function isTokenNotFrozen(Token $token): bool
    {
        return !$this->isTokenFrozen($token);
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

    public function howManyUnfrozenTokenAreThereExceptFrom(TokenInterface $excludedToken): int
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->select('COUNT(t.id) as count_');

        $queryBuilder->andWhere('t.oauthToken != :user_token');
        $queryBuilder->setParameter(':user_token', $excludedToken->getOAuthToken());

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

        $tokenTypeRepository = $this->getEntityManager()->getRepository('Api:TokenType');

        $tokenType = $tokenTypeRepository->findOneBy(['name' => TokenType::USER]);
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

        $this->save($token);

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

    private function save(Token $token): void
    {
        $entityManager = $this->getEntityManager();

        try {
            $entityManager->persist($token);
            $entityManager->flush();
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['token' => $token->getOAuthToken()]);
        }
    }

    public function reconnect(): void {
        $entityManager = $this->getEntityManager();
        $entityManager->getConnection()->connect();
    }
}

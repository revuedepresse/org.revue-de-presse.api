<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\AccessToken\Repository;

use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Http\Repository\TokenTypeRepositoryInterface;
use App\Twitter\Domain\Http\Security\Authorization\AccessTokenInterface;
use App\Twitter\Infrastructure\Http\Client\Fallback\FallbackToken;
use App\Twitter\Infrastructure\Http\Entity\NullToken;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Entity\TokenType;
use App\Twitter\Infrastructure\Http\Exception\UnavailableTokenException;
use App\Twitter\Infrastructure\Database\Connection\ConnectionAwareInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\Exception\UnexpectedAccessTokenProperties;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Throwable;

/**
 * @method TokenInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenInterface[]    findAll()
 * @method TokenInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenRepository extends ServiceEntityRepository implements TokenRepositoryInterface, ConnectionAwareInterface
{
    use LoggerTrait;

    private string $consumerKey;
    private string $consumerSecret;
    /**
     * @var TokenTypeRepositoryInterface
     */
    private TokenTypeRepositoryInterface $tokenTypeRepository;

    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        TokenTypeRepositoryInterface $tokenTypeRepository,
        string $consumerKey,
        string $consumerSecret
    ) {
        parent::__construct($registry, $entityClass);
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->tokenTypeRepository = $tokenTypeRepository;
    }

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

        /** @var TokenType $tokenType */
        $tokenType = $this->tokenTypeRepository->findOneBy(['name' => TokenType::USER]);
        $token->setType($tokenType);

        $token->setAccessToken($properties['oauth_token']);
        $token->setAccessTokenSecret($properties['oauth_token_secret']);

        // Ensure the newly created token is not frozen yet
        // equivalent to setting the frozen until date in the past
        $token->unfreeze();

        return $token;
    }

    public function ensureAccessTokenExists(
        string $oauthToken,
        string $oauthTokenSecret,
        string $consumerKey,
        string $consumerSecret
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
        $token = $this->findOneBy([
            'oauthToken' => $oauthToken->getAccessToken(),
            'consumerKey' => $oauthToken->getConsumerKey()
        ]);

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

    protected function isTokenFrozen(TokenInterface $token): bool
    {
        return $token->getFrozenUntil() !== null &&
            $token->getFrozenUntil()->getTimestamp() >
                (new DateTime('now', new DateTimeZone('UTC')))
                    ->getTimestamp();
    }

    /**
     * @throws Exception
     */
    protected function isTokenNotFrozen(TokenInterface $token): bool
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
        $queryBuilder->setParameter(':user_token', $excludedToken->getAccessToken());

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
        $tokenType = $this->tokenTypeRepository->findOneBy(['name' => TokenType::USER]);

        $queryBuilder->andWhere('t.type = :type');
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
        $tokenRepository = $this->getEntityManager()->getRepository(TokenType::class);
        $tokenType = $tokenRepository->findOneBy(['name' => TokenType::APPLICATION]);

        $token = new Token();
        $token->setAccessToken($applicationToken);
        $token->setAccessTokenSecret($accessToken);
        $token->setType($tokenType);
        $token->setCreatedAt(new DateTime());

        $this->save($token);

        return $token;
    }

    public function findFirstUnfrozenToken(): ?TokenInterface
    {
        $queryBuilder = $this->createQueryBuilder('t');

        $this->applyUnfrozenTokenCriteria($queryBuilder);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException) {
            return new FallbackToken();
        }
    }

    /**
     * @return mixed|null
     * @throws NonUniqueResultException
     */
    public function findFirstFrozenToken(): ?TokenInterface
    {
        $queryBuilder = $this->createQueryBuilder('t');

        $tokenRepository = $this->getEntityManager()->getRepository(TokenType::class);
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

    /**
     * @throws \App\Twitter\Infrastructure\Http\Exception\UnexpectedAccessTokenProperties
     */
    private function guardAgainstNullToken(TokenInterface $token): void
    {
        if (
            strlen(trim($token->getAccessToken())) === 0 ||
            strlen(trim($token->getAccessTokenSecret())) === 0 ||
            strlen(trim($token->getConsumerKey())) === 0 ||
            strlen(trim($token->getConsumerSecret())) === 0
        ) {
            UnexpectedAccessTokenProperties::throws('Null Token');
        }
    }

    /**
     * @throws \Exception
     */
    private function save(TokenInterface $token): TokenInterface
    {
        $token->setUpdatedAt(new DateTime('now', new DateTimeZone('UTC')));

        try {
            $this->guardAgainstNullToken($token);

            $exception = new \Exception(
                "TODO: Figuring which class is responsible for trying to record tokens"
            );

            // NOOP
            $this->logger->error(
                $exception->getMessage(),
                [
                    'token' => $token->getOAuthToken(),
                    'stacktrace' => $exception->getTraceAsString()
                ]
            );

            return $token;
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'token' => $token->getAccessToken(),
                    'stacktrace' => $exception->getTraceAsString()
                ]
            );

            return NullToken::fromProps([]);
        }
    }

    public function reconnect(): void {
        $entityManager = $this->getEntityManager();
        $entityManager->getConnection()->connect();
    }

    public function saveAccessToken(AccessTokenInterface $accessToken): TokenInterface
    {
        $this->tokenTypeRepository->ensureTokenTypesExist();

        $existingToken = $this->findOneBy(['oauthToken' => $accessToken->token()]);

        if ($existingToken instanceof TokenInterface) {
            $existingToken->setAccessToken($accessToken->token());
            $existingToken->setAccessTokenSecret($accessToken->secret());

            return $this->save($existingToken);
        }

        $token = $this->makeToken([
            'oauth_token' => $accessToken->token(),
            'oauth_token_secret' => $accessToken->secret()
        ]);
        $token->setConsumerKey($this->consumerKey);
        $token->setConsumerSecret($this->consumerSecret);

        return $this->save($token);
    }
}

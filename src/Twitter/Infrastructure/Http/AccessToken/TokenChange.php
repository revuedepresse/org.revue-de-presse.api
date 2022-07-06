<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\AccessToken;

use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Http\Entity\NullToken;
use App\Twitter\Infrastructure\Http\Exception\CanNotReplaceAccessTokenException;
use App\Twitter\Infrastructure\Http\Exception\UnavailableTokenException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;

class TokenChange implements TokenChangeInterface
{
    private TokenRepositoryInterface $tokenRepository;

    private LoggerInterface $logger;

    public function __construct(
        TokenRepositoryInterface $tokenRepository,
        LoggerInterface $logger
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->logger = $logger;
    }

    /**
     * @param TokenInterface      $excludedToken
     * @param HttpClientInterface $accessor
     * @return TokenInterface
     * @throws CanNotReplaceAccessTokenException
     */
    public function replaceAccessToken(
        TokenInterface $excludedToken,
        HttpClientInterface $accessor
    ): TokenInterface {
        $token = new NullToken;

        $remainingTokensCount = $this->tokenRepository->howManyUnfrozenTokenAreThereExceptFrom($excludedToken);
        if ($remainingTokensCount === 0) {
            CanNotReplaceAccessTokenException::throws($excludedToken);
        }

        try {
            /** @var TokenInterface $token */
            $token = $this->tokenRepository->findTokenOtherThan($excludedToken->getAccessToken());
        } catch (NoResultException|NonUniqueResultException $exception) {
            $this->logger->error($exception->getMessage());
        }

        if (!($token instanceof TokenInterface) ||
            $token instanceof NullToken
        ) {
            UnavailableTokenException::throws(function () {
                return $this->tokenRepository->findFirstFrozenToken();
            });
        }

        $accessor->fromToken($token);

        return $token;
    }
}
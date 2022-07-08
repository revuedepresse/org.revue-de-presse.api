<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\AccessToken;

use App\Twitter\Infrastructure\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Infrastructure\Http\Entity\NullToken;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Entity\TokenInterface;
use App\Twitter\Infrastructure\Http\Exception\UnavailableTokenException;
use App\Twitter\Domain\Http\ApiAccessorInterface;
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

    public function replaceAccessToken(
        TokenInterface $excludedToken,
        ApiAccessorInterface $accessor
    ): TokenInterface {
        $token = new NullToken;

        try {
            /** @var Token $token */
            $token = $this->tokenRepository->findTokenOtherThan($excludedToken->getOAuthToken());
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

        $accessor->setAccessToken($token);

        return $token;
    }
}

<?php
declare(strict_types=1);

namespace App\Infrastructure\Api\AccessToken;

use App\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Infrastructure\Api\Entity\NullToken;
use App\Infrastructure\Api\Entity\Token;
use App\Infrastructure\Api\Entity\TokenInterface;
use App\Infrastructure\Api\Exception\UnavailableTokenException;
use App\Twitter\Api\ApiAccessorInterface;
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
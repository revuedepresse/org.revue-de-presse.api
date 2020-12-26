<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Security\Authentication;

use App\Twitter\Infrastructure\PublishersList\Controller\Exception\InvalidRequestException;
use App\Twitter\Domain\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

trait AuthenticationTokenValidationTrait
{
    public TokenRepositoryInterface $tokenRepository;

    /**
     * @param $corsHeaders
     *
     * @return TokenInterface
     */
    private function guardAgainstInvalidAuthenticationToken($corsHeaders): TokenInterface
    {
        $token = $this->tokenRepository->findFirstUnfrozenToken();
        if (!($token instanceof TokenInterface)) {
            $exceptionMessage = 'Could not process your request at the moment';
            $jsonResponse = new JsonResponse(
                $exceptionMessage,
                503,
                $corsHeaders
            );

            InvalidRequestException::guardAgainstInvalidRequest($jsonResponse, $exceptionMessage);
        }

        return $token;
    }
}

<?php
declare(strict_types=1);

namespace App\Infrastructure\Security\Authentication;

use App\Aggregate\Controller\Exception\InvalidRequestException;
use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use App\Api\Entity\Token;
use Symfony\Component\HttpFoundation\JsonResponse;

trait AuthenticationTokenValidationTrait
{
    public TokenRepositoryInterface $tokenRepository;

    /**
     * @param $corsHeaders
     *
     * @return Token
     * @throws InvalidRequestException
     */
    private function guardAgainstInvalidAuthenticationToken($corsHeaders): Token
    {
        $token = $this->tokenRepository->findFirstUnfrozenToken();
        if (!($token instanceof Token)) {
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

<?php

namespace App\Security;

use App\Aggregate\Controller\Exception\InvalidRequestException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token;
use WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository;

trait AuthenticationTokenValidationTrait
{
    /**
     * @var TokenRepository
     */
    public $tokenRepository;

    /**
     * @param $corsHeaders
     * @return Token
     * @throws NonUniqueResultException
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

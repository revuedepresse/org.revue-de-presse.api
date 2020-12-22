<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Security\Authentication;

use App\Membership\Domain\Entity\AuthenticationToken;
use App\Membership\Infrastructure\Repository\AuthenticationTokenRepository;
use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Exception\InvalidTokenException;
use Auth0\SDK\JWTVerifier;

class Authenticator
{
    public AuthenticationTokenRepository $authenticationTokenRepository;

    public string $authorizedIss;

    public string $validAudience;

    /**
     * @param string $token
     * @return array|null
     * @throws CoreException
     * @throws InvalidTokenException
     */
    public function authenticate(?string $token): ?array
    {
        $verifier = new JWTVerifier([
            'supported_algs' => ['RS256'],
            'valid_audiences' => [$this->validAudience],
            'authorized_iss' => [$this->authorizedIss],
        ]);

        $tokenInfo = $verifier->verifyAndDecode($token);

        $tauthenticationToken = $this->authenticationTokenRepository->findOneBy(
            ['token' => $tokenInfo->sub]
        );

        if ($tauthenticationToken instanceof AuthenticationToken) {
            return (array) $tokenInfo;
        }

        return null;
    }
}

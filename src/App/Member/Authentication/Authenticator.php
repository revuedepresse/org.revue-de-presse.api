<?php

namespace App\Member\Authentication;

use App\Member\Entity\AuthenticationToken;
use App\Member\Repository\AuthenticationTokenRepository;
use Auth0\SDK\JWTVerifier;

class Authenticator
{
    /**
     * @var AuthenticationTokenRepository
     */
    public $authenticationTokenRepository;

    /**
     * @var string
     */
    public $authorizedIss;

    /**
     * @var string
     */
    public $validAudience;

    /**
     * @param string $token
     * @return array|null
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \Auth0\SDK\Exception\InvalidTokenException
     */
    public function authenticate(string $token): ?array
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

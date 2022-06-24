<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Http\Security\Authorization;

interface AuthorizeAccessInterface
{
    public function requestToken(): RequestTokenInterface;

    public function authorizationUrl(
        RequestTokenInterface $token
    ): string;

    public function accessToken(
        RequestTokenInterface $token,
        VerifierInterface $verifier
    ): AccessTokenInterface;
}
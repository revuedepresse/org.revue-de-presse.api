<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Security\Authentication;

use App\Twitter\Infrastructure\Security\Authentication\HttpAuthenticator;

trait HttpAuthenticatorTrait
{
    private HttpAuthenticator $httpAuthenticator;

    public function setHttpAuthenticator(HttpAuthenticator $httpAuthenticator): self
    {
        $this->httpAuthenticator = $httpAuthenticator;

        return $this;
    }
}
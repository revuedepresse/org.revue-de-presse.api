<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Security\Authorization;

use App\Twitter\Domain\Api\Security\Authorization\RequestTokenInterface;

class RequestToken implements RequestTokenInterface
{
    private string $token;
    private string $secret;

    public function __construct(string $token, string $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function secret(): string
    {
        return $this->secret;
    }
}
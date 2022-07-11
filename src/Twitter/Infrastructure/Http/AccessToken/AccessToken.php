<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\AccessToken;

class AccessToken
{
    private string $accessToken;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }
}
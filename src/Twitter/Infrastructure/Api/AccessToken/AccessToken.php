<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\AccessToken;

class AccessToken
{
    /**
     * @var string
     */
    private string $accessToken;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function accessToken()
    {
        return $this->accessToken;
    }
}
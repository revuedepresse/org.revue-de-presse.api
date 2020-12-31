<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Api\Security\Authorization;

use App\Twitter\Domain\Api\Security\Authorization\AccessTokenInterface;

class AccessToken implements AccessTokenInterface
{
    private string $token;
    private string $secret;
    private string $userId;
    private string $screenName;

    public function __construct(
        string $token,
        string $secret,
        string $userId,
        string $screenName
    ) {

        $this->token = $token;
        $this->secret = $secret;
        $this->userId = $userId;
        $this->screenName = $screenName;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function secret(): string
    {
        return $this->secret;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }
}
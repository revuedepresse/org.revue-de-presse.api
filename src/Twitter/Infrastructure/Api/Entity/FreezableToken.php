<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Entity;

use App\Twitter\Domain\Api\Model\TokenInterface;

class FreezableToken extends Token
{
    public static function fromUserToken(string $userToken): TokenInterface
    {
        $token = new self();
        $token->setOAuthToken($userToken);

        return $token;
    }
}

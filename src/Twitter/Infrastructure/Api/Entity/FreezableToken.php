<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Entity;

use App\Twitter\Domain\Api\Model\TokenInterface;

class FreezableToken extends Token
{
    public static function fromAccessToken(
        string $accessToken,
        string $consumerKey
    ): TokenInterface
    {
        $token = new self();
        $token->setAccessToken($accessToken);
        $token->setConsumerKey($consumerKey);

        return $token;
    }
}

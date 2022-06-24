<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Entity;

use App\Twitter\Domain\Http\Model\TokenInterface;

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

<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Exception;

use App\Twitter\Domain\Http\Model\TokenInterface;
use Exception;

class CanNotReplaceAccessTokenException extends Exception
{
    public static function throws(TokenInterface $token): void
    {
        throw new self(
            sprintf(
                'Can not replace "%s" token with another one',
                $token->getAccessToken()
            )
        );
    }
}
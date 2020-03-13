<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\AccessToken;

use App\Twitter\Infrastructure\Http\Entity\TokenInterface;
use App\Twitter\Domain\Api\ApiAccessorInterface;

interface TokenChangeInterface
{
    public function replaceAccessToken(
        TokenInterface $excludedToken,
        ApiAccessorInterface $accessor
    ): TokenInterface;
}

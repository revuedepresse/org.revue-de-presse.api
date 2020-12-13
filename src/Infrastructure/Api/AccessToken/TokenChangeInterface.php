<?php
declare(strict_types=1);

namespace App\Infrastructure\Api\AccessToken;

use App\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Api\ApiAccessorInterface;

interface TokenChangeInterface
{
    public function replaceAccessToken(
        TokenInterface $excludedToken,
        ApiAccessorInterface $accessor
    ): TokenInterface;
}
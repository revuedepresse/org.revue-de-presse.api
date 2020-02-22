<?php
declare(strict_types=1);

namespace App\Api\AccessToken;

use App\Api\Entity\TokenInterface;
use App\Twitter\Api\ApiAccessorInterface;

interface TokenChangeInterface
{
    public function replaceAccessToken(
        TokenInterface $excludedToken,
        ApiAccessorInterface $accessor
    ): TokenInterface;
}
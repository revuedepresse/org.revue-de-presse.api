<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\AccessToken;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Api\ApiAccessorInterface;

interface TokenChangeInterface
{
    public function replaceAccessToken(
        TokenInterface $excludedToken,
        ApiAccessorInterface $accessor
    ): TokenInterface;
}
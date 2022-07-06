<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\AccessToken;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;

interface TokenChangeInterface
{
    public function replaceAccessToken(
        TokenInterface $excludedToken,
        HttpClientInterface $accessor
    ): TokenInterface;
}
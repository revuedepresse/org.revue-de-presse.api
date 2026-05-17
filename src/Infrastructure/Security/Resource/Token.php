<?php
declare(strict_types=1);

namespace App\Infrastructure\Security\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Infrastructure\Security\AccessTokenDto;
use App\Infrastructure\Security\TokenProcessor;

#[ApiResource(
    shortName: 'Token',
    operations: [
        new Post(
            uriTemplate: '/token',
            security: 'is_granted("PUBLIC_ACCESS")',
            input: false,
            output: AccessTokenDto::class,
            processor: TokenProcessor::class,
        ),
    ],
)]
final class Token
{
}

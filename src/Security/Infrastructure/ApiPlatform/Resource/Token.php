<?php
declare(strict_types=1);

namespace App\Security\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Security\Domain\AccessTokenDto;
use App\Security\Infrastructure\ApiPlatform\State\TokenProcessor;

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

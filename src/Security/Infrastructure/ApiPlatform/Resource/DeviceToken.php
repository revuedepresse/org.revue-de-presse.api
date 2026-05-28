<?php
declare(strict_types=1);

namespace App\Security\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Security\Domain\DeviceTokenDto;
use App\Security\Infrastructure\ApiPlatform\State\DeviceTokenProcessor;

/**
 * Public mint endpoint for the native (Compose Multiplatform) clients.
 *
 * Unlike POST /api/token (server-to-server with Basic client credentials), this
 * endpoint takes no shared secret: anything with the right rate-limit budget
 * can request a token. That trade-off is acceptable because:
 *   - the binary cannot carry a secret safely anyway,
 *   - tokens are short-lived,
 *   - the rate limiter enforces a per-IP ceiling.
 */
#[ApiResource(
    shortName: 'DeviceToken',
    operations: [
        new Post(
            uriTemplate: '/device-tokens',
            security: 'is_granted("PUBLIC_ACCESS")',
            input: DeviceTokenMintRequest::class,
            output: DeviceTokenDto::class,
            processor: DeviceTokenProcessor::class,
        ),
    ],
)]
final class DeviceToken
{
}

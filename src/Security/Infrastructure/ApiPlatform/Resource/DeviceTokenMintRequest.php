<?php
declare(strict_types=1);

namespace App\Security\Infrastructure\ApiPlatform\Resource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body shape accepted by POST /api/device-tokens.
 *
 * `installId` is a client-rotated opaque identifier (UUID-shaped); it is not a
 * credential — anyone holding it can mint a token. The rate limiter caps the
 * mint frequency. The native app rotates installId on 403, mirroring the
 * device-locked policy enforced by the API.
 */
final class DeviceTokenMintRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 32)]
        #[Assert\Regex(pattern: '/^[a-z0-9_-]+$/i', message: 'platform must be alphanumeric')]
        public ?string $platform = null,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 32)]
        public ?string $appVersion = null,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 128)]
        #[Assert\Regex(pattern: '/^[a-z0-9_-]+$/i', message: 'installId must be alphanumeric (URL-safe)')]
        public ?string $installId = null,
    ) {
    }
}

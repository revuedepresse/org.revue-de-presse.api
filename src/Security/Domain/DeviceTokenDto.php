<?php
declare(strict_types=1);

namespace App\Security\Domain;

/**
 * Output shape returned by POST /api/device-tokens.
 *
 * Field names match what the native data layer's ApiClient.mintDeviceToken()
 * expects so the Compose Multiplatform binary needs no further adapter.
 */
final readonly class DeviceTokenDto
{
    public function __construct(
        public string $token,
        public int $expiresInSec,
    ) {
    }
}

<?php
declare(strict_types=1);

namespace App\Security\Domain;

/**
 * Mints a short-lived Bearer for an installed app instance — no client secret.
 *
 * All device tokens map onto a single configured "device" Member ID. The Member
 * row exists so the rest of the auth stack (ApiAccessTokenHandler → UserBadge)
 * keeps working unchanged; downstream authorization sees a ROLE_USER principal.
 *
 * Anonymity / abuse is bounded by the rate limiter sitting in front of the
 * /api/device-tokens endpoint (RateLimitSubscriber + limiter.device_token_mint).
 */
final readonly class DeviceTokenMinter
{
    public function __construct(
        private AccessTokenStore $store,
        private string $deviceMemberId,
        private int $ttlSeconds = 900,
    ) {
    }

    public function mint(): DeviceTokenDto
    {
        $token = bin2hex(random_bytes(32));
        $this->store->put($token, $this->deviceMemberId, $this->ttlSeconds);

        return new DeviceTokenDto(token: $token, expiresInSec: $this->ttlSeconds);
    }

    public function ttlSeconds(): int
    {
        return $this->ttlSeconds;
    }
}

<?php
declare(strict_types=1);

namespace App\TikTok\Domain;

use InvalidArgumentException;

/**
 * Value object wrapping the body returned by
 * `POST https://open.tiktokapis.com/v2/oauth/token/` with
 * `grant_type=authorization_code`.
 *
 * The fields not strictly used downstream (`scope`, `open_id`,
 * `refresh_expires_in`, `token_type`) are kept optional so a partial
 * TikTok response still validates instead of crashing the controller.
 */
final readonly class TikTokTokenResponse
{
    /**
     * @param array<string, mixed> $raw Original upstream payload, retained so
     *                                  the controller can return it verbatim.
     */
    public function __construct(
        public string $access_token,
        public string $refresh_token,
        public int $expires_in,
        public ?int $refresh_expires_in,
        public ?string $scope,
        public ?string $open_id,
        public ?string $token_type,
        public array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $accessToken = $payload['access_token'] ?? null;
        if (!is_string($accessToken) || $accessToken === '') {
            throw new InvalidArgumentException('access_token: must be a non-empty string');
        }

        $refreshToken = $payload['refresh_token'] ?? null;
        if (!is_string($refreshToken) || $refreshToken === '') {
            throw new InvalidArgumentException('refresh_token: must be a non-empty string');
        }

        $expiresIn = $payload['expires_in'] ?? null;
        if (!is_int($expiresIn) || $expiresIn <= 0) {
            throw new InvalidArgumentException('expires_in: must be a positive integer');
        }

        $refreshExpiresIn = $payload['refresh_expires_in'] ?? null;
        if ($refreshExpiresIn !== null && (!is_int($refreshExpiresIn) || $refreshExpiresIn <= 0)) {
            throw new InvalidArgumentException('refresh_expires_in: must be a positive integer when present');
        }

        $scope = $payload['scope'] ?? null;
        if ($scope !== null && !is_string($scope)) {
            throw new InvalidArgumentException('scope: must be a string when present');
        }

        $openId = $payload['open_id'] ?? null;
        if ($openId !== null && !is_string($openId)) {
            throw new InvalidArgumentException('open_id: must be a string when present');
        }

        $tokenType = $payload['token_type'] ?? null;
        if ($tokenType !== null && !is_string($tokenType)) {
            throw new InvalidArgumentException('token_type: must be a string when present');
        }

        return new self(
            $accessToken,
            $refreshToken,
            $expiresIn,
            $refreshExpiresIn,
            $scope,
            $openId,
            $tokenType,
            $payload,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}

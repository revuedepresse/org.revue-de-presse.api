<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Crypto;

final class KeyDerivation
{
    public static function deriveAes256Key(string $base64MasterKey, string $infoLabel): string
    {
        $master = base64_decode($base64MasterKey, true);
        if ($master === false || strlen($master) < 32) {
            throw new \RuntimeException('NEWSLETTER_ENCRYPTION_KEY must be base64-encoded 32+ bytes');
        }
        // HKDF with empty salt is acceptable when the input is already high-entropy.
        return hash_hkdf('sha256', $master, 32, $infoLabel);
    }
}

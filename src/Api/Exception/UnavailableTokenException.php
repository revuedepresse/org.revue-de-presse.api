<?php
declare(strict_types=1);

namespace App\Api\Exception;

use App\Api\Entity\TokenInterface;
use RuntimeException;
use function is_callable;

class UnavailableTokenException extends RuntimeException
{
    public static ?\Closure $getFirstAvailableToken = null;

    public static function throws(?Callable $getFirstTokenToBeAvailable = null): void
    {
        if (is_callable($getFirstTokenToBeAvailable)) {
            self::$getFirstAvailableToken = $getFirstTokenToBeAvailable;
        }

        throw new self('There is no access token available.');
    }

    public static function firstTokenToBeAvailable(): ?TokenInterface
    {
        return (self::$getFirstAvailableToken)();
    }
}
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Exception;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Http\Entity\NullToken;
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
        if (!is_callable(self::$getFirstAvailableToken)) {
            return new NullToken();
        }

        return (self::$getFirstAvailableToken)();
    }
}
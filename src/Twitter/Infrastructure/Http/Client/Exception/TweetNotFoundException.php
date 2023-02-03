<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client\Exception;

use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use Psr\Log\LoggerInterface;

class TweetNotFoundException extends UnavailableResourceException
{
    /**
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     */
    public static function throws(
        string $screenName,
        $previous = null,
        LoggerInterface $logger = null
    ): void {
        $exception = new TweetNotFoundException(sprintf(
            'No status was found for member with screen name "%s"',
            $screenName
        ), $previous?->getCode() ?: 0, $previous);

        $logger?->info(
            $exception->getMessage(),
            ['trace' => $exception->getTrace()]
        );

        throw $exception;
    }
}

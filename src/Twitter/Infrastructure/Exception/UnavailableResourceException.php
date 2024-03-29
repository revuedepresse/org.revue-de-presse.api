<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Exception;

use App\Twitter\Domain\Http\Client\TwitterAPIEndpointsAwareInterface;
use App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException;
use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use App\Twitter\Domain\Http\TwitterAPIAwareInterface;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;
use function is_array;
use function is_object;
use function sprintf;

class UnavailableResourceException extends Exception implements TwitterAPIAwareInterface, TwitterAPIEndpointsAwareInterface
{

    /**
     * @throws ProtectedAccountException
     * @throws UnavailableResourceException
     */
    public static function handleUnavailableMemberException(
        Exception $exception,
        LoggerInterface $logger,
        array $options
    ): void {
        $message = self::logUnavailableMemberException(
            $exception,
            $logger,
            $options
        );

        if ($exception instanceof ProtectedAccountException) {
            throw $exception;
        }

        throw new self(
            $message,
            $exception->getCode(),
            $exception
        );
    }

    /**
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
     * @throws OverCapacityException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws SuspendedAccountException
     * @throws UnknownApiAccessException
     * @throws \App\Twitter\Infrastructure\Exception\BlockedFromViewingMemberProfileException
     */
    public static function guardAgainstContentFetchingException(
        $content,
        string $endpoint,
        callable $onApiLimitExceeded
    ): void {
        if (!self::containErrors($content)) {
            return;
        }

        if (isset($content->error)) {
            if ($content->error === 'Not authorized.') {
                throw new ProtectedAccountException(
                    $content->error,
                    self::ERROR_PROTECTED_TWEET
                );
            }

            if ($content->error === 'Read-only application cannot POST.') {
                throw new ReadOnlyApplicationException($content->error);
            }

            UnknownApiAccessException::throws($content->error);
        }

        $errorCode = $content->errors[0]->code;

        /** @var stdClass $error */
        $error = $content->errors[0];

        if ($errorCode === self::ERROR_OVER_CAPACITY) {
            throw new OverCapacityException(
                $error->message,
                $error->code
            );
        }

        if ($errorCode === self::ERROR_NO_STATUS_FOUND_WITH_THAT_ID) {
            throw new TweetNotFoundException(
                $error->message,
                $error->code
            );
        }

        if ($errorCode === self::ERROR_BLOCKED_FROM_VIEWING_MEMBER_PROFILE) {
            throw new BlockedFromViewingMemberProfileException(
                $error->message,
                $error->code
            );
        }

        if ($errorCode === self::ERROR_BAD_AUTHENTICATION_DATA) {
            throw new BadAuthenticationDataException(
                $error->message,
                $error->code
            );
        }

        if ($errorCode === self::ERROR_EXCEEDED_RATE_LIMIT) {
            if (self::exceptWhenAccessingApiRateLimitStatus($endpoint)) {
                $onApiLimitExceeded($endpoint);
            }

            throw new ApiAccessRateLimitException(
                $error->message,
                $error->code
            );
        }

        if (
            $errorCode === self::ERROR_USER_NOT_FOUND
            || $errorCode === self::ERROR_CAN_NOT_FIND_SPECIFIED_USER
            || $errorCode === self::ERROR_NOT_FOUND
        ) {
            throw new NotFoundMemberException(
                $error->message,
                $error->code
            );
        }

        if ($errorCode === self::ERROR_SUSPENDED_USER) {
            throw new SuspendedAccountException(
                $error->message,
                $error->code
            );
        }
    }

    public static function containErrors($response): bool
    {
        return is_object($response)
            && (
                (isset($response->errors, $response->errors[0]) &&
                    is_array($response->errors))
                || isset($response->error));
    }

    private static function logUnavailableMemberException(
        Exception $exception,
        LoggerInterface $logger,
        array $options
    ): string {
        $message = 'Skipping member with screen name "%s", who has not been found';

        if ($exception instanceof SuspendedAccountException) {
            $message = 'Skipping member with screen name "%s", who has been suspended';
        }

        if ($exception instanceof ProtectedAccountException) {
            $message = 'Skipping member with screen name "%s", who is protected';
            $logger->error(
                sprintf(
                    $message,
                    $options[FetchAuthoredTweetInterface::SCREEN_NAME]
                )
            );

            return $message;
        }

        $message = sprintf($message, $options[FetchAuthoredTweetInterface::SCREEN_NAME]);
        $logger->error(
            $message, ['trace' => $exception->getTrace()]
        );

        return $message;
    }

    private static function exceptWhenAccessingApiRateLimitStatus(string $endpoint): bool
    {
        return !str_contains($endpoint, self::API_ENDPOINT_RATE_LIMIT_STATUS);
    }
}

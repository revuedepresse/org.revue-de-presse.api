<?php
declare(strict_types=1);

namespace App\Twitter\Exception;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Twitter\Api\TwitterErrorAwareInterface;
use Exception;
use stdClass;
use function is_array;
use function is_object;

/**
 * @package App\Twitter\Exception
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UnavailableResourceException extends Exception implements TwitterErrorAwareInterface
{
    /**
     * @param stdClass|array $content
     * @param string         $endpoint
     * @param callable       $onApiLimitExceeded
     *
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OverCapacityException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws SuspendedAccountException
     * @throws UnknownApiAccessException
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
            throw new NotFoundStatusException(
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
            $onApiLimitExceeded($endpoint);
            throw new ApiRateLimitingException(
                $error->message,
                $error->code
            );
        }

        if ($errorCode === self::ERROR_USER_NOT_FOUND ||
            $errorCode === self::ERROR_CAN_NOT_FIND_SPECIFIED_USER ||
            $errorCode === self::ERROR_NOT_FOUND) {
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

    /**
     * @param $response
     *
     * @return bool
     */
    public static function containErrors($response): bool
    {
        return is_object($response) && (
                (isset($response->errors, $response->errors[0]) && is_array($response->errors))
                || isset($response->error));
    }
}
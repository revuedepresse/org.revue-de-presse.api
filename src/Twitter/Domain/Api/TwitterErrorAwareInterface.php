<?php

namespace App\Twitter\Domain\Api;

/**
 * Interface TwitterErrorAwareInterface
 *
 * @package App\Twitter\Api
 *
 * @see     https://dev.twitter.com/docs/error-codes-responses
 *
 * error code   6 => cURL error: Could not resolve host
 * error code  32 => Could not authenticate you
 * error code  34 => The specified resource was not found
 * error code  52 => Empty reply from server
 * error code  63 => User has been suspended
 * error code  64 => Your account is suspended and is not permitted to access this feature
 * error code  68 => The Twitter REST API v * error code 1 is no longer active
 * error code  88 => Rate limit exceeded
 * error code  89 => Invalid or expired token
 * error code  92 => SSL is required
 * error code 130 => Twitter is temporarily over capacity.
 * error code 131 => Internal error
 * error code 135 => Could not authenticate you
 * error code 161 => Thrown when a user cannot follow another user due to some kind of limit
 * error code 179 => Thrown when a Tweet cannot be viewed by the authenticating user, usually due to the tweet's author having protected their tweets.
 * error code 185 => User is over daily status update limit
 * error code 187 => Status is a duplicate
 * error code 215 => Bad authentication data
 * error code 226 => This request looks like it might be automated
 * error code 231 => User must verify login
 * error code 251 => Corresponds to a HTTP request to a retired URL.
 * error code 261 => Application cannot perform write actions.
 */
interface TwitterErrorAwareInterface
{
    public const ERROR_HOST_RESOLUTION = 6;

    public const ERROR_AUTHENTICATION = 32;

    public const ERROR_NOT_FOUND = 34;

    public const ERROR_USER_NOT_FOUND = 50;

    public const ERROR_EMPTY_REPLY = 52;

    public const ERROR_SUSPENDED_USER = 63;

    public const ERROR_SUSPENDED_ACCOUNT = 64;

    public const ERROR_INACTIVE_API = 68;

    public const ERROR_EXCEEDED_RATE_LIMIT = 88;

    public const ERROR_INVALID_TOKEN = 89;

    public const ERROR_REQUIRED_SSL = 92;

    public const ERROR_CAN_NOT_FIND_SPECIFIED_USER = 108;

    public const ERROR_OVER_CAPACITY = 130;

    public const ERROR_INTERNAL_ERROR = 131;

    public const ERROR_AUTHENTICATION_OAUTH = 135;

    public const ERROR_NO_STATUS_FOUND_WITH_THAT_ID = 144;

    public const ERROR_LIMITED_FOLLOWING = 161;

    public const ERROR_PROTECTED_TWEET = 179;

    public const ERROR_LIMITED_DAILY_STATUS_UPDATE = 185;

    public const ERROR_DUPLICATE_STATUS = 187;

    public const ERROR_BAD_AUTHENTICATION_DATA = 215;

    public const ERROR_AUTOMATED_REQUEST = 226;

    public const ERROR_REQUIRED_LOGIN_VERIFICATION = 231;

    public const ERROR_RETIRED_URL = 251;

    public const ERROR_UNAUTHORIZED_ACTIONS = 261;

    public const ERROR_CODES = [
        self::ERROR_HOST_RESOLUTION,

        self::ERROR_AUTHENTICATION,

        self::ERROR_NOT_FOUND,

        self::ERROR_USER_NOT_FOUND,

        self::ERROR_EMPTY_REPLY,

        self::ERROR_SUSPENDED_USER,

        self::ERROR_SUSPENDED_ACCOUNT,

        self::ERROR_INACTIVE_API,

        self::ERROR_EXCEEDED_RATE_LIMIT,

        self::ERROR_INVALID_TOKEN,

        self::ERROR_REQUIRED_SSL,

        self::ERROR_CAN_NOT_FIND_SPECIFIED_USER,

        self::ERROR_OVER_CAPACITY,

        self::ERROR_INTERNAL_ERROR,

        self::ERROR_AUTHENTICATION_OAUTH,

        self::ERROR_NO_STATUS_FOUND_WITH_THAT_ID,

        self::ERROR_LIMITED_FOLLOWING,

        self::ERROR_PROTECTED_TWEET,

        self::ERROR_LIMITED_DAILY_STATUS_UPDATE,

        self::ERROR_DUPLICATE_STATUS,

        self::ERROR_BAD_AUTHENTICATION_DATA,

        self::ERROR_AUTOMATED_REQUEST,

        self::ERROR_REQUIRED_LOGIN_VERIFICATION,

        self::ERROR_RETIRED_URL,

        self::ERROR_UNAUTHORIZED_ACTIONS,
    ];
}

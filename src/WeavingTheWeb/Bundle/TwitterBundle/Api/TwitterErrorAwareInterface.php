<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Api;

/**
 * Interface TwitterErrorAwareInterface
 * @package WeavingTheWeb\Bundle\TwitterBundle\Api
 *
 * @see https://dev.twitter.com/docs/error-codes-responses
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
    const ERROR_HOST_RESOLUTION = 6;

    const ERROR_AUTHENTICATION = 32;

    const ERROR_NOT_FOUND = 34;

    const ERROR_USER_NOT_FOUND = 50;

    const ERROR_EMPTY_REPLY = 52;

    const ERROR_SUSPENDED_USER = 63;

    const ERROR_SUSPENDED_ACCOUNT = 64;

    const ERROR_INACTIVE_API = 68;

    const ERROR_EXCEEDED_RATE_LIMIT = 88;

    const ERROR_INVALID_TOKEN = 89;

    const ERROR_REQUIRED_SSL = 92;

    const ERROR_OVER_CAPACITY = 130;

    const ERROR_INTERNAL_ERROR = 131;

    const ERROR_AUTHENTICATION_OAUTH = 135;

    const ERROR_LIMITED_FOLLOWING = 161;

    const ERROR_PROTECTED_TWEET = 179;

    const ERROR_LIMITED_DAILY_STATUS_UPDATE = 185;

    const ERROR_DUPLICATE_STATUS = 187;

    const ERROR_BAD_AUTHENTICATION_DATA = 215;

    const ERROR_AUTOMATED_REQUEST = 226;

    const ERROR_REQUIRED_LOGIN_VERIFICATION = 231;

    const ERROR_RETIRED_URL = 251;

    const ERROR_UNAUTHORIZED_ACTIONS = 261;
}

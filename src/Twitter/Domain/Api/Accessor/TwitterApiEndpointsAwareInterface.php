<?php

namespace App\Twitter\Domain\Api\Accessor;

interface TwitterApiEndpointsAwareInterface
{
    public const API_ENDPOINT_MEMBERS_LISTS = '/lists/members/create_all';

    public const API_ENDPOINT_OWNERSHIPS = '/lists/ownerships';

    public const API_ENDPOINT_RATE_LIMIT_STATUS = '/application/rate_limit_status';
}
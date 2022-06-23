<?php

namespace App\Twitter\Domain\Api\Accessor;

interface TwitterApiEndpointsAwareInterface
{
    public const API_ENDPOINT_GET_MEMBERS_LISTS = '/lists/members';

    public const API_ENDPOINT_MEMBERS_LISTS = '/lists/members/create_all';

    public const API_ENDPOINT_MEMBERS_LISTS_VERSION_2 = '/lists/:id/members';

    public const API_ENDPOINT_REMOVE_MEMBERS_FROM_LISTS = '/lists/members/destroy_all';

    public const API_ENDPOINT_OWNERSHIPS = '/lists/ownerships';

    public const API_ENDPOINT_RATE_LIMIT_STATUS = '/application/rate_limit_status';
}
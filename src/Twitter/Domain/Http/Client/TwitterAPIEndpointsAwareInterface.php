<?php

namespace App\Twitter\Domain\Http\Client;

interface TwitterAPIEndpointsAwareInterface
{
    public const API_ENDPOINT_CREATE_SAVED_SEARCHES = '/saved_searches/create';

    public const API_ENDPOINT_GET_MEMBERS_LISTS = '/lists/members';

    public const API_ENDPOINT_GET_MEMBER_PROFILE = '/users/show';

    public const API_ENDPOINT_MEMBERS_LISTS = '/lists/members/create_all';

    public const API_ENDPOINT_MEMBERS_LISTS_VERSION_2 = '/lists/:list_id/members';

    public const API_ENDPOINT_MEMBER_TIMELINE = '/statuses/user_timeline';

    public const API_ENDPOINT_REMOVE_MEMBERS_FROM_LISTS = '/lists/members/destroy_all';

    public const API_ENDPOINT_SEARCH_TWEETS = '/search/tweets.json';

    public const API_ENDPOINT_OWNERSHIPS = '/lists/ownerships';

    public const API_ENDPOINT_RATE_LIMIT_STATUS = '/application/rate_limit_status';

    public const API_GRAPHQL_ENDPOINT_MEMBER_PROFILE = '/graphql/oUZZZ8Oddwxs8Cd3iW3UEA/UserByScreenName';

    public const API_GRAPHQL_ENDPOINT_MEMBER_TIMELINE = '/graphql/2GIWTr7XwadIixZDtyXd4A/UserTweets';
}

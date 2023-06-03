<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

trait HttpSearchParamReducerTrait
{
    public function reduceParameters(string $endpoint, array $parameters): array
    {
        $queryString = parse_url(
            str_replace('?&', '?', $endpoint),
            PHP_URL_QUERY
        );

        $callingGraphqlServer = preg_match('#^https://api.twitter.com/graphql#', $endpoint) > 0;

        if (!is_string($queryString) && !$callingGraphqlServer) {
            throw new \Exception('Invalid query param');
        }

        $queryParams = explode(
            '&',
            $queryString
        );

        return array_reduce(
            $queryParams,
            function ($parameters, $queryParam) {
                $keyValue                 = explode('=', $queryParam);
                $parameters[$keyValue[0]] = $keyValue[1];

                return $parameters;
            },
            $parameters
        );
    }

}

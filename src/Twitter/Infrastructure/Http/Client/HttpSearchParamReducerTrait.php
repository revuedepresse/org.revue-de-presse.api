<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

trait HttpSearchParamReducerTrait
{
    public function reduceParameters(string $endpoint, array $parameters): array
    {
        $queryString = parse_url($endpoint, PHP_URL_QUERY);

        if (!is_string($queryString)) {
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

<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

trait HttpSearchParamReducerTrait
{
    public function reduceParameters(string $endpoint, array $parameters): array
    {
        $queryParams = explode(
            '&',
            parse_url($endpoint, PHP_URL_QUERY)
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

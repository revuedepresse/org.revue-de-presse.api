<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

interface HttpSearchParamReducerInterface
{
    const HTTP_METHOD_POST  = 'post';

    const HTTP_METHOD_GET   = 'get';

    public function reduceParameters(string $endpoint, array $parameters): array;
}
